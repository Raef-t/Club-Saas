<?php

namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\SubscriptionPlan;

class EloquentSubscriptionPlanRepository implements SubscriptionPlanRepositoryInterface
{
    public function all()
    {
        return SubscriptionPlan::active()->get();
    }

    public function find(int $id)
    {
        return SubscriptionPlan::findOrFail($id);
    }

    public function create(array $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $plan = SubscriptionPlan::create($data);
            if (isset($data['activities']) && is_array($data['activities'])) {
                $activities = $this->prepareActivities($data['activities']);
                $plan->planActivities()->createMany($activities);
            }
            if (isset($data['session_templates']) && is_array($data['session_templates'])) {
                $plan->sessionTemplates()->createMany($data['session_templates']);
            }
            return $plan;
        });
    }

    public function update(int $id, array $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $plan = $this->find($id);
            $plan->update($data);
            if (isset($data['activities']) && is_array($data['activities'])) {
                $activities = $this->prepareActivities($data['activities']);
                $plan->planActivities()->delete();
                $plan->planActivities()->createMany($activities);
            }
            if (isset($data['session_templates']) && is_array($data['session_templates'])) {
                $plan->sessionTemplates()->delete();
                $plan->sessionTemplates()->createMany($data['session_templates']);
            }
            return $plan;
        });
    }

    /**
     * Get pre-flight delete check info for a subscription plan.
     */
    public function getDeleteCheckInfo(int $id): array
    {
        $plan = $this->find($id);

        $activeCount = $plan->playerSubscriptions()->where('status', 'active')->count();
        $inactiveCount = $plan->playerSubscriptions()->whereIn('status', ['inactive', 'terminated', 'expired'])->count();

        $planActivities = $plan->planActivities()->with('staffActivity.staff.person')->get();
        $coaches = [];
        foreach ($planActivities as $planAct) {
            if ($planAct->staffActivity && $planAct->staffActivity->staff) {
                $staff = $planAct->staffActivity->staff;
                $person = $staff->person;
                $staffId = $staff->id;
                if (!isset($coaches[$staffId])) {
                    $coaches[$staffId] = [
                        'staff_id' => $staffId,
                        'name' => $person ? ($person->full_name ?? ("Staff #" . $staffId)) : ("Staff #" . $staffId),
                        'role' => $staff->role,
                    ];
                }
            }
        }

        return [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'has_active_subscriptions' => $activeCount > 0,
            'active_subscriptions_count' => $activeCount,
            'inactive_subscriptions_count' => $inactiveCount,
            'associated_coaches' => array_values($coaches),
        ];
    }

    /**
     * Delete a subscription plan with options for active player subscriptions and staff.
     */
    public function delete(int $id, array $options = [])
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $options) {
            $plan = $this->find($id);

            // 1. Detach from offers pivot table
            \Illuminate\Support\Facades\DB::table('offer_subscription_plan')
                ->where('subscription_plan_id', $plan->id)
                ->delete();

            // 2. Soft delete inactive player subscriptions (inactive, terminated, expired)
            $plan->playerSubscriptions()
                ->whereIn('status', ['inactive', 'terminated', 'expired'])
                ->delete();

            // 3. Soft delete active player subscriptions if explicitly confirmed
            if (!empty($options['force_delete_active_subscriptions'])) {
                $plan->playerSubscriptions()
                    ->where('status', 'active')
                    ->delete();
            }

            // 4. Detach and soft delete requested staff/coaches if associated with the plan
            if (!empty($options['detach_and_delete_staff_ids']) && is_array($options['detach_and_delete_staff_ids'])) {
                $allowedCoachIds = $plan->planActivities()
                    ->with('staffActivity')
                    ->get()
                    ->pluck('staffActivity.staff_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                $invalidStaffIds = array_diff($options['detach_and_delete_staff_ids'], $allowedCoachIds);

                if (!empty($invalidStaffIds)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'detach_and_delete_staff_ids' => [
                            __('المدربين التاليين غير مرتبطين بهذه الخطة: ') . implode(', ', $invalidStaffIds)
                        ]
                    ]);
                }

                foreach ($options['detach_and_delete_staff_ids'] as $staffId) {
                    \Modules\Sports\Models\StaffActivity::where('staff_id', $staffId)->delete();

                    $staff = \Modules\StaffManager\Models\Staff::find($staffId);
                    if ($staff) {
                        $staff->delete();
                    }
                }
            }

            // 5. Soft delete the plan itself (cascades to planActivities, sessionTemplates, exceptions)
            return $plan->delete();
        });
    }

    protected function prepareActivities(array $activities)
    {
        $prepared = [];
        foreach ($activities as $act) {
            $activityId = $act['activity_id'] ?? null;
            $coachId = $act['coach_id'] ?? null;
            
            if ($activityId) {
                $staffActivity = \Modules\Sports\Models\StaffActivity::firstOrCreate([
                    'activity_id' => $activityId,
                    'staff_id' => $coachId,
                ]);
                $prepared[] = ['staff_activity_id' => $staffActivity->id];
            }
        }
        return $prepared;
    }
}

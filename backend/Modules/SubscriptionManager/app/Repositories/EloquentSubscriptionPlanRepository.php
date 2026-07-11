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

    public function delete(int $id)
    {
        $plan = $this->find($id);
        return $plan->delete();
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

<?php
namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Repositories\SubscriptionPlanActivityRepositoryInterface;

class SubscriptionPlanActivityService
{
    protected $repository;

    public function __construct(SubscriptionPlanActivityRepositoryInterface $repository) {
        $this->repository = $repository;
    }

    public function getAll(array $filters = []) { 
        if (!empty($filters['branch_id'])) {
            return \Modules\SubscriptionManager\Models\SubscriptionPlanActivity::whereHas('plan', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            })->get();
        }
        return $this->repository->all(); 
    }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { 
        $this->validateBusinessRules($data);

        $activityId = $data['activity_id'] ?? null;
        $coachId = $data['coach_id'] ?? null;
        $planId = $data['plan_id'] ?? null;

        if (isset($data['activity_id'])) {
            $staffActivity = \Modules\Sports\Models\StaffActivity::firstOrCreate([
                'activity_id' => $data['activity_id'],
                'staff_id' => $data['coach_id'] ?? null,
            ]);
            $data['staff_activity_id'] = $staffActivity->id;
            unset($data['activity_id'], $data['coach_id']);
        }
        $result = $this->repository->create($data); 

        if ($planId && ($coachId || $activityId)) {
            $updateData = [];
            if ($coachId) $updateData['coach_id'] = $coachId;
            if ($activityId) $updateData['activity_id'] = $activityId;

            \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::whereHas('subscription', function($q) use ($planId) {
                $q->where('plan_id', $planId);
            })->update($updateData);
        }

        return $result;
    }

    public function update($id, array $data) { 
        $current = $this->getById($id);
        
        $activityId = $data['activity_id'] ?? $current->activity_id;
        $coachId = array_key_exists('coach_id', $data) ? $data['coach_id'] : $current->coach_id;
        $planId = $data['plan_id'] ?? $current->plan_id;

        $this->validateBusinessRules([
            'plan_id' => $planId,
            'activity_id' => $activityId,
            'coach_id' => $coachId,
        ]);

        if (array_key_exists('activity_id', $data) || array_key_exists('coach_id', $data)) {
            $staffActivity = \Modules\Sports\Models\StaffActivity::firstOrCreate([
                'activity_id' => $activityId,
                'staff_id' => $coachId,
            ]);
            $data['staff_activity_id'] = $staffActivity->id;
            unset($data['activity_id'], $data['coach_id']);
        }
        $result = $this->repository->update($id, $data); 

        if ($planId) {
            $updateData = [];
            if ($coachId !== null) $updateData['coach_id'] = $coachId;
            if ($activityId !== null) $updateData['activity_id'] = $activityId;

            if (!empty($updateData)) {
                \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::whereHas('subscription', function($q) use ($planId) {
                    $q->where('plan_id', $planId);
                })->update($updateData);
            }
        }

        return $result;
    }

    protected function validateBusinessRules(array $data)
    {
        $planId = $data['plan_id'] ?? null;
        $activityId = $data['activity_id'] ?? null;
        $coachId = $data['coach_id'] ?? null;

        $plan = $planId ? \Modules\SubscriptionManager\Models\SubscriptionPlan::find($planId) : null;
        $activity = $activityId ? \Modules\Sports\Models\Activity::find($activityId) : null;

        if ($plan && $activity && $plan->branch_id !== $activity->branch_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'activity_id' => __('النشاط المحدد لا ينتمي لنفس الفرع الخاص بالخطة.'),
            ]);
        }

        $branchId = $plan ? $plan->branch_id : ($activity ? $activity->branch_id : null);

        if ($coachId) {
            $coach = \Modules\StaffManager\Models\Staff::with(['branches', 'activeContract'])->find($coachId);
            
            if ($coach && $branchId) {
                if (!$coach->branches->contains('id', $branchId)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'coach_id' => __('الكوتش المحدد لا ينتمي للفرع الخاص بهذه الخطة/النشاط.'),
                    ]);
                }
            }

            if ($coach && $activity) {
                $activity->loadMissing('activityType');
                $employmentType = $coach->activeContract?->employment_type ?? 'fixed_salary';
                $isSessionBased = (bool) ($activity->activityType?->is_session_based ?? false);

                if ($employmentType === 'fixed_salary' && $isSessionBased) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'coach_id' => [__('لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية.')],
                    ]);
                }

                if ($employmentType === 'commission_based' && !$isSessionBased) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'coach_id' => [__('لا يمكن الربط بسبب عدم توافق طبيعة عمل المدرب مع نوع الفعالية.')],
                    ]);
                }
            }

            if ($activityId) {
                $staffActivityExists = \Modules\Sports\Models\StaffActivity::where('activity_id', $activityId)
                    ->where('staff_id', $coachId)
                    ->exists();

                if (!$staffActivityExists) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'coach_id' => __('هذا النشاط غير مربوط مع هذا الكوتش، الرجاء ربط النشاط بالكوتش أولاً.'),
                    ]);
                }
            }
        }
    }



    public function delete($id) { return $this->repository->delete($id); }
    public function getTrashed() { return $this->repository->getTrashed(); }
    public function restore($id) { return $this->repository->restore($id); }
}

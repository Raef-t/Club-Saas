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
        if (isset($data['activity_id'])) {
            $staffActivity = \Modules\Sports\Models\StaffActivity::firstOrCreate([
                'activity_id' => $data['activity_id'],
                'staff_id' => $data['coach_id'] ?? null,
            ]);
            $data['staff_activity_id'] = $staffActivity->id;
            unset($data['activity_id'], $data['coach_id']);
        }
        return $this->repository->create($data); 
    }

    public function update($id, array $data) { 
        if (array_key_exists('activity_id', $data) || array_key_exists('coach_id', $data)) {
            $current = $this->getById($id);
            $activityId = $data['activity_id'] ?? $current->activity_id;
            $coachId = array_key_exists('coach_id', $data) ? $data['coach_id'] : $current->coach_id;

            $staffActivity = \Modules\Sports\Models\StaffActivity::firstOrCreate([
                'activity_id' => $activityId,
                'staff_id' => $coachId,
            ]);
            $data['staff_activity_id'] = $staffActivity->id;
            unset($data['activity_id'], $data['coach_id']);
        }
        return $this->repository->update($id, $data); 
    }

    public function delete($id) { return $this->repository->delete($id); }
}

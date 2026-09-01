<?php
namespace Modules\SubscriptionManager\Repositories;

use Modules\SubscriptionManager\Models\SubscriptionPlanActivity;

class EloquentSubscriptionPlanActivityRepository implements SubscriptionPlanActivityRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = SubscriptionPlanActivity::query();
        if (!empty($filters['branch_id'])) {
            $query->whereHas('plan', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }
        if ((isset($filters['per_page']) && $filters['per_page'] === 'all') || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = isset($filters['per_page']) ? min(max((int)$filters['per_page'], 1), 100) : 15;
        return $query->paginate($perPage);
    }
    public function find($id) { return SubscriptionPlanActivity::findOrFail($id); }
    public function create(array $data) { return SubscriptionPlanActivity::create($data); }
    public function update($id, array $data) {
        $record = $this->find($id);
        $record->update($data);
        $record->refresh();
        return $record;
    }
    public function delete($id) { return $this->find($id)->delete(); }
    public function getTrashed(array $filters = [])
    {
        $query = SubscriptionPlanActivity::onlyTrashed();
        if (!empty($filters['branch_id'])) {
            $query->whereHas('plan', function($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }
        if ((isset($filters['per_page']) && $filters['per_page'] === 'all') || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }
        $perPage = isset($filters['per_page']) ? min(max((int)$filters['per_page'], 1), 100) : 15;
        return $query->paginate($perPage);
    }
    public function restore($id) {
        $activity = SubscriptionPlanActivity::onlyTrashed()->findOrFail($id);
        $activity->restore();
        return $activity;
    }
}

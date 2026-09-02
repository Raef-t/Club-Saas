<?php

namespace Modules\ClubManager\Repositories;

use Modules\ClubManager\Models\Locker;

class EloquentLockerRepository implements LockerRepositoryInterface
{
    public function all(array $filters = [])
    {
        $query = Locker::with(['branch']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function find($id)
    {
        return Locker::findOrFail($id);
    }

    public function create(array $data)
    {
        return Locker::create($data);
    }

    public function update($id, array $data)
    {
        $locker = $this->find($id);
        $locker->update($data);
        return $locker;
    }

    public function delete($id)
    {
        $locker = $this->find($id);
        return $locker->delete();
    }

    public function getByBranch($branchId)
    {
        return Locker::where('branch_id', $branchId)->get();
    }

    public function getTrashed(array $filters = [])
    {
        $query = Locker::onlyTrashed()->with(['branch']);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!isset($filters['per_page']) || $filters['per_page'] === 'all' || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->get();
        }

        $perPage = min(max((int)$filters['per_page'], 1), 100);
        return $query->paginate($perPage);
    }

    public function restore($id)
    {
        $locker = Locker::onlyTrashed()->findOrFail($id);
        $locker->restore();
        return $locker;
    }
}

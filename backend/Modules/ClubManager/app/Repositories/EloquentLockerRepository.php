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
}

<?php

namespace Modules\BranchManager\Repositories;

use Modules\BranchManager\Models\Locker;

class EloquentLockerRepository implements LockerRepositoryInterface
{
    public function all()
    {
        return Locker::with(['branch', 'facility'])->get();
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

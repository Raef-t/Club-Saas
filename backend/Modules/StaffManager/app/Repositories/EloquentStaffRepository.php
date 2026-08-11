<?php

namespace Modules\StaffManager\Repositories;

use Modules\StaffManager\Models\Staff;

class EloquentStaffRepository implements StaffRepositoryInterface
{
    public function all()
    {
        return Staff::all();
    }

    public function find($id)
    {
        return Staff::with(['shifts'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Staff::create($data);
    }

    public function update($id, array $data)
    {
        $staff = $this->find($id);
        $staff->update($data);
        return $staff;
    }

    public function delete($id)
    {
        $staff = $this->find($id);
        return $staff->delete();
    }

    public function getCoaches()
    {
        return Staff::where('role', 'coach')->get();
    }

    public function getTrashed(array $filters = [])
    {
        $query = Staff::onlyTrashed()->with(['coachDetail', 'branches', 'user']);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->latest()->get();
    }

    public function restore(int $id)
    {
        $staff = Staff::onlyTrashed()->findOrFail($id);
        $staff->restore();
        return $staff;
    }
}

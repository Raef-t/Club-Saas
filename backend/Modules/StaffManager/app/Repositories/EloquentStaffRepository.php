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
        $query = Staff::onlyTrashed()->with([
            'person.contacts',
            'coachDetail',
            'branches',
            'user',
        ]);


        if (!empty($filters['branch_id'])) {
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->where('staff_branches.branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if ((isset($filters['per_page']) && $filters['per_page'] === 'all') || (isset($filters['paginate']) && filter_var($filters['paginate'], FILTER_VALIDATE_BOOLEAN) === false) || (isset($filters['all']) && filter_var($filters['all'], FILTER_VALIDATE_BOOLEAN) === true)) {
            return $query->latest()->get();
        }

        $perPage = isset($filters['per_page']) ? min(max((int)$filters['per_page'], 1), 100) : 15;
        return $query->latest()->paginate($perPage);
    }

    public function restore(int $id)
    {
        $staff = Staff::onlyTrashed()->findOrFail($id);
        $staff->restore();
        return $staff;
    }
}

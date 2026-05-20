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
        return Staff::with(['shifts', 'attendances'])->findOrFail($id);
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
}

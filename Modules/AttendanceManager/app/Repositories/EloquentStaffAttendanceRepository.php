<?php

namespace Modules\AttendanceManager\Repositories;

use Modules\AttendanceManager\Models\StaffAttendance;

class EloquentStaffAttendanceRepository implements StaffAttendanceRepositoryInterface
{
    public function all()
    {
        return StaffAttendance::all();
    }

    public function find($id)
    {
        return StaffAttendance::findOrFail($id);
    }

    public function create(array $data)
    {
        return StaffAttendance::create($data);
    }

    public function update($id, array $data)
    {
        $attendance = $this->find($id);
        $attendance->update($data);
        return $attendance;
    }

    public function delete($id)
    {
        $attendance = $this->find($id);
        return $attendance->delete();
    }

    public function findOpenAttendance($staffId)
    {
        return StaffAttendance::where('staff_id', $staffId)
            ->whereNull('check_out')
            ->first();
    }

    public function getHistory($staffId, $from = null, $to = null)
    {
        $query = StaffAttendance::where('staff_id', $staffId)->orderBy('check_in', 'desc');

        if ($from) {
            $query->whereDate('check_in', '>=', $from);
        }
        if ($to) {
            $query->whereDate('check_in', '<=', $to);
        }

        return $query->get();
    }
}

<?php

namespace Modules\AttendanceManager\Repositories;

use Modules\AttendanceManager\Models\MemberAttendance;

class EloquentMemberAttendanceRepository implements MemberAttendanceRepositoryInterface
{
    public function all()
    {
        return MemberAttendance::all();
    }

    public function find($id)
    {
        return MemberAttendance::findOrFail($id);
    }

    public function create(array $data)
    {
        return MemberAttendance::create($data);
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

    public function findOpenAttendance($memberId)
    {
        return MemberAttendance::where('member_id', $memberId)
            ->whereNull('check_out')
            ->first();
    }

    public function getHistory($memberId, $from = null, $to = null)
    {
        $query = MemberAttendance::where('member_id', $memberId)->orderBy('check_in', 'desc');

        if ($from) {
            $query->whereDate('check_in', '>=', $from);
        }
        if ($to) {
            $query->whereDate('check_in', '<=', $to);
        }

        return $query->get();
    }
}

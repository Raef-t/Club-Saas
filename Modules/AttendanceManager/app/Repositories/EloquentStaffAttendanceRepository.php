<?php

namespace Modules\AttendanceManager\Repositories;

use Modules\AttendanceManager\Models\Attendance;

class EloquentStaffAttendanceRepository implements StaffAttendanceRepositoryInterface
{
    public function all()
    {
        return Attendance::where('attendable_type', 'staff')->get();
    }

    public function find($id)
    {
        return Attendance::where('attendable_type', 'staff')->findOrFail($id);
    }

    public function create(array $data)
    {
        $attendableId = $data['staff_id'] ?? $data['attendable_id'] ?? null;
        $branchId = $data['branch_id'] ?? $data['facility_id'] ?? null;
        $clubId = $data['club_id'] ?? null;
        $checkInAt = $data['check_in'] ?? $data['check_in_at'] ?? now();
        $checkOutAt = $data['check_out'] ?? $data['check_out_at'] ?? null;
        $status = $data['status'] ?? 'checked_in';
        $metadata = $data['metadata'] ?? [];

        if (isset($data['notes'])) {
            $metadata['notes'] = $data['notes'];
        }

        return Attendance::create([
            'club_id' => $clubId,
            'attendable_type' => 'staff',
            'attendable_id' => $attendableId,
            'branch_id' => $branchId,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'status' => $status,
            'metadata' => $metadata,
        ]);
    }

    public function update($id, array $data)
    {
        $attendance = $this->find($id);
        $updateData = [];

        if (isset($data['staff_id'])) $updateData['attendable_id'] = $data['staff_id'];
        if (isset($data['attendable_id'])) $updateData['attendable_id'] = $data['attendable_id'];
        if (isset($data['branch_id'])) $updateData['branch_id'] = $data['branch_id'];
        if (isset($data['facility_id'])) $updateData['branch_id'] = $data['facility_id'];
        if (isset($data['club_id'])) $updateData['club_id'] = $data['club_id'];
        if (isset($data['check_in'])) $updateData['check_in_at'] = $data['check_in'];
        if (isset($data['check_in_at'])) $updateData['check_in_at'] = $data['check_in_at'];
        if (isset($data['check_out'])) $updateData['check_out_at'] = $data['check_out'];
        if (isset($data['check_out_at'])) $updateData['check_out_at'] = $data['check_out_at'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        
        if (isset($data['notes'])) {
            $metadata = $attendance->metadata ?? [];
            $metadata['notes'] = $data['notes'];
            $updateData['metadata'] = $metadata;
        }

        if (isset($data['metadata'])) {
            $updateData['metadata'] = $data['metadata'];
        }

        $attendance->update($updateData);
        return $attendance;
    }

    public function delete($id)
    {
        $attendance = $this->find($id);
        return $attendance->delete();
    }

    public function findOpenAttendance($staffId)
    {
        return Attendance::where('attendable_type', 'staff')
            ->where('attendable_id', $staffId)
            ->whereNull('check_out_at')
            ->first();
    }

    public function getHistory($staffId, $from = null, $to = null)
    {
        $query = Attendance::where('attendable_type', 'staff')
            ->where('attendable_id', $staffId)
            ->orderBy('check_in_at', 'desc');

        if ($from) {
            $query->whereDate('check_in_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('check_in_at', '<=', $to);
        }

        return $query;
    }
}

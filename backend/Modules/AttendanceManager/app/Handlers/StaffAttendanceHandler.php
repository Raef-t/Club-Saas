<?php

namespace Modules\AttendanceManager\Handlers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\AttendanceManager\Contracts\AttendanceHandlerInterface;
use Modules\AttendanceManager\DTOs\AttendanceDecision;
use Modules\AttendanceManager\DTOs\CheckInAttempt;
use Modules\AttendanceManager\Events\StaffCheckedIn;
use Modules\AttendanceManager\Events\StaffCheckedOut;
use Modules\AttendanceManager\Models\Attendance;

class StaffAttendanceHandler implements AttendanceHandlerInterface
{
    /**
     * Check in a staff member (includes coaches — coach is a Staff with role='coach').
     *
     * Flow:
     *  1. Ensure no open check-in exists.
     *  2. Validate via StaffAttendancePolicy (branch assignment, active status).
     *  3. Create Attendance record (attendable_type='staff', attendable_id=staff_id).
     *  4. Fire StaffCheckedIn event.
     */
    public function checkIn(int $entityId, int $branchId): Attendance
    {
        return DB::transaction(function () use ($entityId, $branchId) {

            // 1. No double check-in
            $open = $this->findOpenAttendance($entityId);
            if ($open) {
                throw new Exception(__('Staff member is already checked in.'));
            }

            // 2. Policy validation (branch assignment, active status)
            if (app()->bound('attendance.policy.staff')) {
                /** @var \Modules\AttendanceManager\Contracts\AttendancePolicy $policy */
                $policy = app('attendance.policy.staff');

                $attempt = new CheckInAttempt(
                    attendableType: 'staff',
                    attendableId: $entityId,
                    clubId: 1, // Optional: You can remove clubId from CheckInAttempt if it's no longer used
                    branchId: $branchId,
                    timestamp: new \DateTimeImmutable()
                );

                /** @var AttendanceDecision $decision */
                $decision = $policy->authorize($attempt);

                if (!$decision->isAllowed) {
                    throw new Exception($decision->rejectionReason);
                }
            }

            $checkInAt = now();

            // 3. Create Attendance record
            $attendance = Attendance::create([
                'attendable_type' => 'staff',
                'attendable_id'   => $entityId,
                'branch_id'       => $branchId,
                'check_in_at'     => $checkInAt,
                'status'          => 'checked_in',
            ]);

            event(new StaffCheckedIn($attendance));

            return $attendance;
        });
    }

    /**
     * Check out a staff member.
     */
    public function checkOut(int $attendanceId): Attendance
    {
        return DB::transaction(function () use ($attendanceId) {
            /** @var Attendance $attendance */
            $attendance = Attendance::where('attendable_type', 'staff')
                ->findOrFail($attendanceId);

            if ($attendance->check_out_at !== null) {
                throw new Exception(__('Staff member is already checked out.'));
            }

            $checkOutAt      = now();
            $durationMinutes = Carbon::parse($attendance->check_in_at)->diffInMinutes($checkOutAt);

            $attendance->update([
                'check_out_at'     => $checkOutAt,
                'duration_minutes' => $durationMinutes,
                'status'           => 'completed',
            ]);

            event(new StaffCheckedOut($attendance));

            return $attendance->fresh();
        });
    }

    /**
     * Find the current open check-in for a staff member, if any.
     */
    public function findOpenAttendance(int $entityId): ?Attendance
    {
        return Attendance::where('attendable_type', 'staff')
            ->where('attendable_id', $entityId)
            ->where('status', 'checked_in')
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();
    }

    /**
     * Return a history query for a given staff member.
     */
    public function getHistory(int $entityId, ?string $from = null, ?string $to = null): Builder
    {
        $query = Attendance::where('attendable_type', 'staff')
            ->where('attendable_id', $entityId)
            ->orderByDesc('check_in_at');

        if ($from) {
            $query->whereDate('check_in_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('check_in_at', '<=', $to);
        }

        return $query;
    }
}

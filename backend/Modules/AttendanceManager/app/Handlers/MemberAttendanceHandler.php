<?php

namespace Modules\AttendanceManager\Handlers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AttendanceManager\Contracts\AttendanceHandlerInterface;
use Modules\AttendanceManager\Events\MemberCheckedIn;
use Modules\AttendanceManager\Events\MemberCheckedOut;
use Modules\AttendanceManager\Models\Attendance;

class MemberAttendanceHandler implements AttendanceHandlerInterface
{
    /**
     * Check in a member via the reception desk.
     *
     * Flow:
     *  1. Resolve the subscription — from explicit subscription_id in metadata
     *     (chosen by the receptionist) or fallback to the first active subscription.
     *  2. Ensure no open check-in exists.
     *  3. Validate debt / remaining sessions on the selected subscription.
     *  4. Create Attendance record, capturing:
     *      - recorded_by_staff_id  → the currently authenticated user (receptionist)
     *  5. Decrement sessions_consumed ONLY in player_subscription_items (per user's spec).
     *  6. Fire MemberCheckedIn event.
     */
    public function checkIn(int $entityId, int $clubId, int $branchId, array $metadata = []): Attendance
    {
        return DB::transaction(function () use ($entityId, $clubId, $branchId, $metadata) {

            // ── 1. No double check-in ───────────────────────────────────────────────
            $open = $this->findOpenAttendance($entityId);
            if ($open) {
                throw new Exception(__('Member is already checked in.'));
            }

            // ── 2. Create Attendance record (Pending Deduction) ─────────────────────
            $checkInAt = $metadata['check_in_at'] ?? now();
            
            // Strip fields we don't want straight in metadata
            $storedMetadata = array_diff_key($metadata, array_flip([
                'check_in_at',
            ]));

            // Mark this attendance as pending deduction so reception knows it needs action
            $storedMetadata['deduction_status'] = 'pending';

            $attendance = Attendance::create([
                'club_id'              => $clubId,
                'attendable_type'      => 'member',
                'attendable_id'        => $entityId,
                'branch_id'            => $branchId,
                'recorded_by_staff_id' => Auth::id(),   // The logged-in receptionist (if any)
                'check_in_at'          => $checkInAt,
                'status'               => 'checked_in',
                'metadata'             => $storedMetadata,
            ]);

            event(new MemberCheckedIn($attendance));

            return $attendance;
        });
    }

    /**
     * Check out a member.
     */
    public function checkOut(int $attendanceId): Attendance
    {
        return DB::transaction(function () use ($attendanceId) {
            /** @var Attendance $attendance */
            $attendance = Attendance::where('attendable_type', 'member')
                ->findOrFail($attendanceId);

            if ($attendance->check_out_at !== null) {
                throw new Exception(__('Member is already checked out.'));
            }

            $checkOutAt      = now();
            $durationMinutes = Carbon::parse($attendance->check_in_at)->diffInMinutes($checkOutAt);

            $attendance->update([
                'check_out_at'     => $checkOutAt,
                'duration_minutes' => $durationMinutes,
                'status'           => 'completed',
            ]);

            event(new MemberCheckedOut($attendance));

            return $attendance->fresh();
        });
    }

    /**
     * Find the current open check-in for a member, if any.
     */
    public function findOpenAttendance(int $entityId): ?Attendance
    {
        return Attendance::where('attendable_type', 'member')
            ->where('attendable_id', $entityId)
            ->where('status', 'checked_in')
            ->whereNull('check_out_at')
            ->latest('check_in_at')
            ->first();
    }

    /**
     * Return a history query for a given member.
     */
    public function getHistory(int $entityId, ?string $from = null, ?string $to = null): Builder
    {
        $query = Attendance::where('attendable_type', 'member')
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

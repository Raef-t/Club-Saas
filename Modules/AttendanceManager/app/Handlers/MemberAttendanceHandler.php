<?php

namespace Modules\AttendanceManager\Handlers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\AttendanceManager\Contracts\AttendanceHandlerInterface;
use Modules\AttendanceManager\Events\MemberCheckedIn;
use Modules\AttendanceManager\Events\MemberCheckedOut;
use Modules\AttendanceManager\Models\Attendance;

class MemberAttendanceHandler implements AttendanceHandlerInterface
{
    /**
     * Check in a member.
     *
     * Flow:
     *  1. Find the member's active PlayerSubscription.
     *  2. Ensure no open check-in exists.
     *  3. Validate debt / remaining sessions.
     *  4. Create Attendance record (attendable_type='member', attendable_id=member_id).
     *  5. Decrement remaining_sessions atomically inside the same transaction.
     *  6. Fire MemberCheckedIn event.
     */
    public function checkIn(int $entityId, int $clubId, int $branchId, array $metadata = []): Attendance
    {
        return DB::transaction(function () use ($entityId, $clubId, $branchId, $metadata) {

            // 1. Find active subscription
            $subscription = DB::table('player_subscriptions')
                ->where('member_id', $entityId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                throw new Exception(__('Member does not have an active subscription.'));
            }

            // 2. Check no open check-in
            $open = $this->findOpenAttendance($entityId);
            if ($open) {
                throw new Exception(__('Member is already checked in.'));
            }

            // 3. Validate debt
            if ($subscription->remaining_amount > 0) {
                $settings = DB::table('club_settings')
                    ->where('club_id', $clubId)
                    ->first();

                $allowedDebt   = $settings->allowed_debt_limit ?? 0;
                $gracePeriod   = $settings->grace_period_days  ?? 0;

                if ($subscription->remaining_amount > $allowedDebt) {
                    throw new Exception(__(
                        'Access denied: Outstanding debt (:amount) exceeds the allowed limit (:limit).',
                        ['amount' => $subscription->remaining_amount, 'limit' => $allowedDebt]
                    ));
                }

                $startDate = Carbon::parse($subscription->start_date);
                if (now()->diffInDays($startDate) > $gracePeriod) {
                    throw new Exception(__(
                        'Access denied: Grace period for payment has expired. Outstanding balance: :amount.',
                        ['amount' => $subscription->remaining_amount]
                    ));
                }
            }

            // 4. Validate remaining sessions (session-based plans only)
            if ($subscription->remaining_sessions !== null && $subscription->remaining_sessions <= 0) {
                throw new Exception(__('No remaining sessions in the active subscription.'));
            }

            // 5. Create Attendance record
            $attendance = Attendance::create([
                'club_id'        => $clubId,
                'attendable_type' => 'member',
                'attendable_id'  => $entityId,
                'branch_id'      => $branchId,
                'check_in_at'    => now(),
                'status'         => 'checked_in',
                'metadata'       => array_merge($metadata, [
                    'subscription_id'       => $subscription->id,
                    'sessions_before_checkin' => $subscription->remaining_sessions,
                ]),
            ]);

            // 6. Decrement sessions atomically (only if session-based)
            if ($subscription->remaining_sessions !== null) {
                DB::table('player_subscriptions')
                    ->where('id', $subscription->id)
                    ->decrement('remaining_sessions');
            }

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

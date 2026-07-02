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
     *      - locker_id             → locker chosen by the receptionist (nullable)
     *  5. Decrement sessions_consumed ONLY in player_subscription_items (per user's spec).
     *  6. If a locker was selected, mark it as 'rented' and link it to this attendance.
     *  7. Fire MemberCheckedIn event.
     */
    public function checkIn(int $entityId, int $clubId, int $branchId, array $metadata = []): Attendance
    {
        return DB::transaction(function () use ($entityId, $clubId, $branchId, $metadata) {

            // ── 1. Resolve the subscription ────────────────────────────────────────
            $subscriptionId = $metadata['subscription_id'] ?? null;

            if ($subscriptionId) {
                // Receptionist explicitly chose a subscription
                $subscription = DB::table('player_subscriptions')
                    ->where('id', $subscriptionId)
                    ->where('member_id', $entityId)
                    ->where('status', 'active')
                    ->first();

                if (!$subscription) {
                    throw new Exception(__('The selected subscription is not active or does not belong to this member.'));
                }
            } else {
                // Fallback: pick the first active subscription
                $subscription = DB::table('player_subscriptions')
                    ->where('member_id', $entityId)
                    ->where('status', 'active')
                    ->first();

                if (!$subscription) {
                    throw new Exception(__('Member does not have an active subscription.'));
                }
            }

            // ── 2. No double check-in ───────────────────────────────────────────────
            $open = $this->findOpenAttendance($entityId);
            if ($open) {
                throw new Exception(__('Member is already checked in.'));
            }

            // ── 3. Validate debt ───────────────────────────────────────────────────
            if ($subscription->remaining_amount > 0) {
                $settings = DB::table('club_settings')
                    ->where('club_id', $clubId)
                    ->first();

                $allowedDebt = $settings->allowed_debt_limit ?? 0;
                $gracePeriod = $settings->grace_period_days  ?? 0;

                if ($subscription->remaining_amount > $allowedDebt) {
                    throw new Exception(__('Access denied: Outstanding debt (:amount) exceeds the allowed limit (:limit).', [
                        'amount' => $subscription->remaining_amount,
                        'limit'  => $allowedDebt,
                    ]));
                }

                $startDate = Carbon::parse($subscription->start_date);
                if (now()->diffInDays($startDate) > $gracePeriod) {
                    throw new Exception(__('Access denied: Grace period for payment has expired. Outstanding balance: :amount.', [
                        'amount' => $subscription->remaining_amount,
                    ]));
                }
            }

            // ── 4. Validate remaining sessions in items (session-based plans) ──────
            // We check whether at least one item still has sessions left.
            // Unlimited items always pass.
            $activityId = $metadata['activity_id'] ?? null;

            $itemsQuery = DB::table('player_subscription_items')
                ->where('player_subscription_id', $subscription->id)
                ->where('is_unlimited', false);

            if ($activityId) {
                $itemsQuery->where('activity_id', $activityId);
            }

            $items = $itemsQuery->get();

            // If there are session-based items, at least one must have sessions left
            if ($items->isNotEmpty()) {
                $hasAvailable = $items->contains(
                    fn($item) => $item->sessions_consumed < $item->sessions_allocated
                );

                if (!$hasAvailable) {
                    throw new Exception(__('No remaining sessions in the selected subscription.'));
                }
            }

            // ── 5. Resolve locker (if chosen) ──────────────────────────────────────
            $lockerId = $metadata['locker_id'] ?? null;
            if ($lockerId) {
                $locker = DB::table('lockers')->where('id', $lockerId)->first();
                if (!$locker) {
                    throw new Exception(__('Selected locker not found.'));
                }
                if ($locker->status !== 'available') {
                    throw new Exception(__('Locker :number is not available.', ['number' => $locker->locker_number]));
                }
            }

            // ── 6. Build metadata stored in the attendance record ──────────────────
            $checkInAt = $metadata['check_in_at'] ?? now();
            // Strip fields promoted to dedicated columns
            $storedMetadata = array_diff_key($metadata, array_flip([
                'check_in_at',
                'subscription_id',
                'locker_id',
            ]));

            $storedMetadata['subscription_id']         = $subscription->id;
            $storedMetadata['sessions_before_checkin'] = $subscription->remaining_sessions;

            // ── 7. Create Attendance record ─────────────────────────────────────────
            $attendance = Attendance::create([
                'club_id'              => $clubId,
                'attendable_type'      => 'member',
                'attendable_id'        => $entityId,
                'branch_id'            => $branchId,
                'recorded_by_staff_id' => Auth::id(),   // The logged-in receptionist
                'locker_id'            => $lockerId,
                'check_in_at'          => $checkInAt,
                'status'               => 'checked_in',
                'metadata'             => $storedMetadata,
            ]);

            // ── 8. Decrement sessions_consumed in player_subscription_items ONLY ───
            // (Per business spec: deduct from items, NOT from player_subscriptions.remaining_sessions)
            foreach ($items as $item) {
                if ($item->sessions_consumed < $item->sessions_allocated) {
                    DB::table('player_subscription_items')
                        ->where('id', $item->id)
                        ->increment('sessions_consumed');
                    break; // Deduct from the first eligible item only
                }
            }

            // ── 9. Assign the locker ────────────────────────────────────────────────
            if ($lockerId) {
                DB::table('lockers')->where('id', $lockerId)->update([
                    'status'                => 'rented',
                    'current_attendance_id' => $attendance->id,
                    'updated_at'            => now(),
                ]);
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

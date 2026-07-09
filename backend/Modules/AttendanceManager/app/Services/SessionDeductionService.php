<?php

namespace Modules\AttendanceManager\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\AttendanceManager\Models\Attendance;
use Modules\NotificationManager\Services\NotificationService;
use Modules\MemberManager\Models\Member;

class SessionDeductionService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Deducts a session from the specified subscription for the given attendance record.
     * 
     * @param int $attendanceId
     * @param int $subscriptionId
     * @return Attendance
     * @throws Exception
     */
    public function deductSession(int $attendanceId, int $subscriptionId): Attendance
    {
        return DB::transaction(function () use ($attendanceId, $subscriptionId) {
            $attendance = Attendance::where('attendable_type', 'member')->findOrFail($attendanceId);
            $metadata = $attendance->metadata ?? [];

            if (isset($metadata['deduction_status']) && $metadata['deduction_status'] === 'completed') {
                throw new Exception(__('Session already deducted for this attendance.'));
            }

            $subscription = DB::table('player_subscriptions')
                ->where('id', $subscriptionId)
                ->where('member_id', $attendance->attendable_id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                throw new Exception(__('The selected subscription is not active or does not belong to this member.'));
            }

            $this->validateDebt($subscription, $attendance->club_id);

            $items = DB::table('player_subscription_items')
                ->where('player_subscription_id', $subscription->id)
                ->where('is_unlimited', false)
                ->get();

            $this->validateRemainingSessions($items);

            $deductedItem = $this->decrementSessionsConsumed($items);

            $attendance = $this->updateAttendanceMetadata($attendance, $subscription, $metadata);

            $this->sendNotification($attendance, $deductedItem);

            return $attendance;
        });
    }

    private function validateDebt($subscription, $clubId): void
    {
        if ($subscription->remaining_amount > 0) {
            $settings = DB::table('club_settings')->where('club_id', $clubId)->first();
            $allowedDebt = $settings->allowed_debt_limit ?? 0;
            $gracePeriod = $settings->grace_period_days ?? 0;

            if ($subscription->remaining_amount > $allowedDebt) {
                throw new Exception(__('Access denied: Outstanding debt exceeds the allowed limit.'));
            }

            $startDate = \Carbon\Carbon::parse($subscription->start_date);
            if (now()->diffInDays($startDate) > $gracePeriod) {
                throw new Exception(__('Access denied: Grace period for payment has expired.'));
            }
        }
    }

    private function validateRemainingSessions($items): void
    {
        if ($items->isNotEmpty()) {
            $hasAvailable = $items->contains(
                fn($item) => $item->sessions_consumed < $item->sessions_allocated
            );

            if (!$hasAvailable) {
                throw new Exception(__('No remaining sessions in the selected subscription.'));
            }
        }
    }

    private function decrementSessionsConsumed($items)
    {
        $deductedItem = null;
        foreach ($items as $item) {
            if ($item->sessions_consumed < $item->sessions_allocated) {
                DB::table('player_subscription_items')
                    ->where('id', $item->id)
                    ->increment('sessions_consumed');
                $deductedItem = $item;
                break;
            }
        }
        return $deductedItem;
    }

    private function updateAttendanceMetadata(Attendance $attendance, $subscription, array $metadata): Attendance
    {
        $metadata['subscription_id'] = $subscription->id;
        $metadata['deduction_status'] = 'completed';
        $metadata['deducted_by_staff_id'] = Auth::id();

        $attendance->update([
            'metadata' => $metadata
        ]);

        return $attendance;
    }

    private function sendNotification(Attendance $attendance, $deductedItem): void
    {
        $member = Member::with('person.user')->find($attendance->attendable_id);
        $userId = $member?->person?->user?->id;

        if ($userId) {
            if ($deductedItem) {
                $remaining = $deductedItem->sessions_allocated - ($deductedItem->sessions_consumed + 1);
                $this->notificationService->createNotification([
                    'title' => 'تم خصم جلسة',
                    'body' => "تم تسجيل حضورك وخصم جلسة من اشتراكك بنجاح. الجلسات المتبقية: {$remaining}",
                    'user_ids' => [$userId],
                    'sender_type' => 'system'
                ]);
            } else {
                $this->notificationService->createNotification([
                    'title' => 'تسجيل حضور',
                    'body' => 'تم تسجيل حضورك بنجاح. نتمنى لك تمريناً ممتعاً!',
                    'user_ids' => [$userId],
                    'sender_type' => 'system'
                ]);
            }
        }
    }
}

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
     * Automatically find an active subscription for a member and deduct a session.
     * Used mainly for Gate entries where there's no receptionist to pick a subscription.
     *
     * @param int $attendanceId
     * @param int $memberId
     * @return Attendance
     * @throws Exception
     */
    public function autoDeductSessionForGate(int $attendanceId, int $memberId): Attendance
    {
        $subscription = DB::table('player_subscriptions')
            ->where('member_id', $memberId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->orderBy('id', 'asc') // You can sort by closest expiry date later if needed
            ->first();

        if (!$subscription) {
            throw new Exception(__('No active subscription found. Access denied (Subscription might be frozen or expired).'));
        }

        return $this->deductSession($attendanceId, $subscription->id);
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

            $attendance = $this->updateAttendanceMetadata($attendance, $subscription, $metadata, $deductedItem);

            $planName = DB::table('subscription_plans')->where('id', $subscription->plan_id)->value('name') ?? 'اشتراك';
            $this->sendNotification($attendance, $planName, $deductedItem);

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

    private function updateAttendanceMetadata(Attendance $attendance, $subscription, array $metadata, $deductedItem): Attendance
    {
        $metadata['subscription_id'] = $subscription->id;
        $metadata['deduction_status'] = 'completed';
        $metadata['deducted_by_staff_id'] = Auth::id();
        
        if ($deductedItem) {
            $metadata['deducted_item_id'] = $deductedItem->id;
        }

        $attendance->update([
            'metadata' => $metadata
        ]);

        return $attendance;
    }

    /**
     * Rollbacks a session deduction and cancels the attendance.
     *
     * @param int $attendanceId
     * @return void
     * @throws Exception
     */
    public function rollbackDeduction(int $attendanceId): void
    {
        DB::transaction(function () use ($attendanceId) {
            $attendance = Attendance::where('attendable_type', 'member')->findOrFail($attendanceId);
            $metadata = $attendance->metadata ?? [];

            $member = Member::with('person.user')->find($attendance->attendable_id);
            $userId = $member?->person?->user?->id;
            $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';

            $remainingSessions = null;
            $isSessionBased = false;

            if (isset($metadata['deduction_status']) && $metadata['deduction_status'] === 'completed') {
                if (isset($metadata['deducted_item_id'])) {
                    $isSessionBased = true;
                    $item = DB::table('player_subscription_items')
                        ->where('id', $metadata['deducted_item_id'])
                        ->first();

                    if ($item && $item->sessions_consumed > 0) {
                        DB::table('player_subscription_items')
                            ->where('id', $metadata['deducted_item_id'])
                            ->decrement('sessions_consumed');
                        
                        $remainingSessions = $item->sessions_allocated - ($item->sessions_consumed - 1);
                    } elseif ($item) {
                        $remainingSessions = $item->sessions_allocated - $item->sessions_consumed;
                    }
                }
            }

            // Finally, delete the attendance record
            $attendance->delete();

            if ($userId) {
                if ($isSessionBased && $remainingSessions !== null) {
                    $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_rollback_session')->first();
                    if ($template) {
                        $body = $template->parseBody([
                            'اسم اللاعب' => $playerName,
                            'الجلسات المتبقية' => $remainingSessions
                        ]);
                        $title = $template->subject ?? 'إلغاء دخول واسترجاع جلسة 🔄';
                    } else {
                        $title = 'إلغاء دخول واسترجاع جلسة 🔄';
                        $body = "عزيزي {$playerName}، تم إلغاء تسجيل دخولك الأخير واسترجاع الجلسة إلى رصيدك. الجلسات المتبقية لك الآن: {$remainingSessions} جلسة.";
                    }
                } else {
                    $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_rollback_time')->first();
                    if ($template) {
                        $body = $template->parseBody([
                            'اسم اللاعب' => $playerName
                        ]);
                        $title = $template->subject ?? 'إلغاء تسجيل الدخول 🔄';
                    } else {
                        $title = 'إلغاء تسجيل الدخول 🔄';
                        $body = "عزيزي {$playerName}، تم إلغاء تسجيل دخولك الأخير بنجاح. نتمنى رؤيتك قريباً!";
                    }
                }

                $this->notificationService->createNotification([
                    'title' => $title,
                    'body' => $body,
                    'user_ids' => [$userId],
                    'sender_type' => 'system'
                ]);
            }
        });
    }

    private function sendNotification(Attendance $attendance, string $planName, $deductedItem): void
    {
        $member = Member::with('person.user')->find($attendance->attendable_id);
        $userId = $member?->person?->user?->id;
        $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';

        if ($userId) {
            $now = \Carbon\Carbon::now();
            $date = $now->format('Y-m-d');
            $dayName = $now->locale('ar')->translatedFormat('l');
            $time = $now->locale('ar')->translatedFormat('h:i A');

            if ($deductedItem) {
                $remaining = $deductedItem->sessions_allocated - ($deductedItem->sessions_consumed + 1);
                $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_session_based')->first();

                if ($template) {
                    $body = $template->parseBody([
                        'اسم اللاعب' => $playerName,
                        'التاريخ' => $date,
                        'اليوم' => $dayName,
                        'الوقت' => $time,
                        'اسم الاشتراك' => $planName,
                        'الجلسات المتبقية' => $remaining,
                    ]);
                    $title = $template->subject ?? 'خصم جلسة تسجيل حضور';
                } else {
                    $title = 'خصم جلسة تسجيل حضور';
                    $body = "أهلاً بك {$playerName}، تم تسجيل حضورك وخصم جلسة من اشتراكك بنجاح. الجلسات المتبقية: {$remaining}";
                }
            } else {
                $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'attendance_time_based')->first();

                if ($template) {
                    $body = $template->parseBody([
                        'اسم اللاعب' => $playerName,
                        'التاريخ' => $date,
                        'اليوم' => $dayName,
                        'الوقت' => $time,
                        'اسم الاشتراك' => $planName,
                    ]);
                    $title = $template->subject ?? 'تسجيل حضور ناجح';
                } else {
                    $title = 'تسجيل حضور ناجح';
                    $body = "أهلاً بك {$playerName}، تم تسجيل حضورك بنجاح. نتمنى لك تمريناً ممتعاً!";
                }
            }

            $this->notificationService->createNotification([
                'title' => $title,
                'body' => $body,
                'user_ids' => [$userId],
                'sender_type' => 'system'
            ]);
        }
    }
}

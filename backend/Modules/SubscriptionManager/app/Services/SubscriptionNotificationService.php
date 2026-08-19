<?php

namespace Modules\SubscriptionManager\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\NotificationManager\Models\NotificationTemplate;
use Modules\NotificationManager\Services\NotificationService;
use Modules\MemberManager\Models\Member;

class SubscriptionNotificationService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send warning notifications for subscriptions expiring in a given number of days.
     *
     * @param int $daysBefore
     * @return void
     */
    public function sendUpcomingExpirationWarnings(int $daysBefore = 3): void
    {
        try {
            // Determine the exact date when the subscription will expire
            $targetDate = Carbon::now()->addDays($daysBefore)->toDateString();

            // Fetch subscriptions expiring on the target date
            $expiringSubscriptions = PlayerSubscription::with(['plan'])
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereDate('end_date', $targetDate)
                ->get();

            if ($expiringSubscriptions->isEmpty()) {
                return;
            }

            $template = NotificationTemplate::where('system_key', 'subscription_expiration_warning')->first();
            $targetDayName = Carbon::parse($targetDate)->locale('ar')->translatedFormat('l');

            foreach ($expiringSubscriptions as $subscription) {
                // Get the user corresponding to the member
                $member = Member::with('person.user')->find($subscription->member_id);
                $userId = $member?->person?->user?->id;
                $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';
                $planName = $subscription?->plan?->name ?? 'اشتراك';

                if ($userId) {
                    if ($template) {
                        $body = $template->parseBody([
                            'اسم اللاعب' => $playerName,
                            'اسم الاشتراك' => $planName,
                            'تاريخ الانتهاء' => $targetDate,
                            'اليوم' => $targetDayName,
                        ]);
                        $title = $template->subject ?? 'تذكير هام: اقتراب موعد انتهاء الاشتراك ⏰';
                    } else {
                        $title = 'تذكير هام: اقتراب موعد انتهاء الاشتراك ⏰';
                        $body = "أهلاً بك {$playerName}، نود تذكيرك بأن اشتراكك \"{$planName}\" سينتهي قريباً بتاريخ {$targetDate} الموافق ليوم {$targetDayName}. سارع بتجديد اشتراكك لضمان استمرارية تمارينك ولياقتك معنا!";
                    }

                    $this->notificationService->createNotification([
                        'title' => $title,
                        'body' => $body,
                        'user_ids' => [$userId],
                        'sender_type' => 'system'
                    ]);
                }
            }

            Log::info("تم إرسال إشعارات قرب انتهاء الاشتراكات بنجاح", ['count' => $expiringSubscriptions->count()]);

        } catch (\Exception $e) {
            Log::error("خطأ أثناء إرسال إشعارات قرب انتهاء الاشتراكات: " . $e->getMessage());
        }
    }
}

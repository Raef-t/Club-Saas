<?php

namespace Modules\NotificationManager\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\NotificationManager\Events\NotificationCreated;
use App\Services\FirebaseService;
use Modules\Authentication\Models\UserDevice;

class SendNotificationListener
{
    public function __construct(
        private readonly FirebaseService $firebaseService
    ) {}

    /**
     * هذا المستمع يُطلق عند إنشاء إشعار جديد.
     */
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        Log::info('🔔 تم إطلاق حدث إنشاء إشعار، جاري التجهيز للإرسال عبر Firebase', [
            'notification_id'  => $notification->id,
            'title'            => $notification->title,
            'recipients_count' => $notification->recipients()->count(),
        ]);

        $this->sendFcmNotification($notification);
    }

    /**
     * جلب توكنات FCM للمستلمين وإرسال الإشعار عبر Firebase
     */
    private function sendFcmNotification($notification): void
    {
        // 1. جلب معرّفات المستخدمين المستهدفين
        $userIds = $notification->recipients()->pluck('user_id')->toArray();

        if (empty($userIds)) {
            Log::warning('⚠️ لم يتم العثور على مستلمين لهذا الإشعار.', ['notification_id' => $notification->id]);
            return;
        }

        // 2. جلب جميع توكنات الأجهزة (FCM Tokens) الخاصة بهؤلاء المستخدمين
        $tokens = UserDevice::whereIn('user_id', $userIds)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            Log::info('ℹ️ المستخدمون المستهدفون ليس لديهم أجهزة مسجلة (لا يوجد FCM Tokens).', [
                'notification_id' => $notification->id
            ]);
            return;
        }

        // 3. تجهيز البيانات الإضافية (Data payload) التي سيستلمها تطبيق الموبايل لفتح شاشة معينة
        $dataPayload = [
            'notification_id' => $notification->id,
            'type'            => 'general', // يمكن توسيع هذا لاحقاً بناءً على الحدث
            'click_action'    => 'FLUTTER_NOTIFICATION_CLICK', // مألوف لتطبيقات Flutter
        ];

        // 4. الإرسال الفعلي عبر خدمة Firebase الموجودة في مشروعك
        $result = $this->firebaseService->sendToMultipleTokens(
            $tokens,
            $notification->title,
            $notification->body,
            $dataPayload
        );

        if ($result['success']) {
            Log::info('✅ تمت عملية الإرسال لـ Firebase بنجاح', [
                'notification_id' => $notification->id,
                'success_count'   => $result['success_count'] ?? 0,
                'failure_count'   => $result['failure_count'] ?? 0,
            ]);
        } else {
            Log::error('❌ فشل عملية الإرسال لـ Firebase', [
                'notification_id' => $notification->id,
                'error'           => $result['error'] ?? 'Unknown Error',
            ]);
        }
    }
}

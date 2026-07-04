<?php

namespace Modules\NotificationManager\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\NotificationManager\Events\NotificationCreated;

class SendNotificationListener
{
    /**
     * هذا المستمع يُطلق عند إنشاء إشعار جديد.
     * أضف منطق إرسال الإشعارات هنا (FCM, SMS, Email, etc.)
     */
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;

        Log::info('🔔 تم إطلاق حدث إنشاء إشعار', [
            'notification_id' => $notification->id,
            'title'           => $notification->title,
            'recipients_count' => $notification->recipients()->count(),
        ]);

        // TODO: إضافة منطق إرسال الإشعارات (FCM, SMS, etc.)
        // مثال: $this->sendFcmNotification($notification);
    }
}

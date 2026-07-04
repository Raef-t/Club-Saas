<?php

namespace Modules\NotificationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Resource لقائمة إشعارات المستخدم (خفيف وسريع)
 */
class UserNotificationListResource extends JsonResource
{
    public function toArray($request): array
    {
        $notification = $this->notification;

        return [
            // المعرف الأساسي هو ID سجل الاستقبال
            'recipient_id'     => $this->id,
            'notification_id'  => $notification->id,
            'title'            => $notification->title,
            'preview'          => Str::limit($notification->body, 120),

            'sender' => [
                'id'   => $notification->sender_id,
                'type' => $notification->sender_type,
            ],

            // حالة المستلم فقط
            'is_read'       => !is_null($this->read_at),
            'read_at'       => $this->read_at,
            'delivered_at'  => $this->delivered_at,

            'has_attachments'   => $notification->attachments->isNotEmpty(),
            'attachments_count' => $notification->attachments->count(),

            'created_at'       => $this->created_at,
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}

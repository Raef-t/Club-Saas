<?php

namespace Modules\NotificationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Resource لتفاصيل إشعار في لوحة الإدارة (يشمل جميع البيانات)
 */
class NotificationAdminDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        if (!$this->relationLoaded('notification') || !$this->notification) {
            return ['error' => 'بيانات الإشعار غير متوفرة'];
        }

        $notification    = $this->notification;
        $recipientsCount = $notification->relationLoaded('recipients')
            ? $notification->recipients->count()
            : 0;

        return [
            'recipient' => [
                'id'              => $this->id,
                'user_id'         => $this->user_id,
                'notification_id' => $this->notification_id,
                'read_at'         => $this->read_at?->toDateTimeString(),
                'delivered_at'    => $this->delivered_at?->toDateTimeString(),
                'is_read'         => !is_null($this->read_at),
            ],

            'notification' => [
                'id'    => $notification->id,
                'title' => $notification->title,
                'body'  => $notification->body,

                'sender' => [
                    'type'         => $notification->sender_type,
                    'id'           => $notification->sender_id,
                    'display_name' => $this->getSenderDisplayName($notification->sender_type),
                ],

                'target_snapshot'  => $notification->target_snapshot, // متاح للإدارة فقط
                'recipients_count' => $recipientsCount,

                'attachments' => $notification->attachments->map(function ($attachment) {
                    return [
                        'id'             => $attachment->id,
                        'name'           => $attachment->file_name,
                        'url'            => Storage::url($attachment->file_path),
                        'path'           => $attachment->file_path,
                        'mime_type'      => $attachment->mime_type,
                        'size'           => $attachment->size,
                        'size_formatted' => $this->formatBytes($attachment->size),
                    ];
                })->values(),

                'created_at' => $notification->created_at?->toDateTimeString(),
                'updated_at' => $notification->updated_at?->toDateTimeString(),
            ],
        ];
    }

    private function getSenderDisplayName(?string $type): string
    {
        return match ($type) {
            'admin'  => 'الإدارة',
            'system' => 'النظام',
            'user'   => 'مستخدم',
            default  => 'غير معروف',
        };
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

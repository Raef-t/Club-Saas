<?php

namespace Modules\NotificationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Resource لتفاصيل إشعار للمستخدم العادي (بدون بيانات حساسة)
 */
class NotificationUserDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        if (!$this->relationLoaded('notification') || !$this->notification) {
            return ['error' => 'بيانات الإشعار غير متوفرة'];
        }

        $notification = $this->notification;

        return [
            // بيانات سجل الاستقبال
            'recipient' => [
                'id'               => $this->id,
                'notification_id'  => $this->notification_id,
                'received_at'      => $this->created_at?->toDateTimeString(),
                'received_at_human' => $this->created_at?->diffForHumans(),
                'read_at'          => $this->read_at?->toDateTimeString(),
                'read_at_human'    => $this->read_at?->diffForHumans(),
                'delivered_at'     => $this->delivered_at?->toDateTimeString(),
                'is_read'          => !is_null($this->read_at),
                'status'           => $this->read_at ? 'read' : ($this->delivered_at ? 'delivered' : 'pending'),
            ],

            // بيانات الإشعار (بدون target_snapshot)
            'notification' => [
                'id'     => $notification->id,
                'title'  => $notification->title,
                'body'   => $notification->body,
                'target_snapshot' => $notification->target_snapshot,

                'sender' => [
                    'type'         => $notification->sender_type ?? 'system',
                    'display_name' => $this->getSenderDisplayName($notification->sender_type),
                ],

                'attachments' => $notification->attachments->map(function ($attachment) {
                    $filePath = ltrim($attachment->file_path, '/');
                    $url = Storage::url($filePath);
                    $url = str_replace('//storage', '/storage', $url);

                    return [
                        'id'             => $attachment->id,
                        'name'           => $attachment->file_name,
                        'url'            => $url,
                        'mime_type'      => $attachment->mime_type,
                        'size'           => $attachment->size,
                        'size_formatted' => $this->formatBytes($attachment->size),
                        'is_image'       => in_array($attachment->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
                    ];
                })->values(),

                'created_at'       => $notification->created_at?->toDateTimeString(),
                'created_at_human' => $notification->created_at?->diffForHumans(),
            ],
        ];
    }

    private function getSenderDisplayName(?string $type): string
    {
        return match ($type) {
            'admin'  => 'الإدارة',
            'system' => 'النظام',
            'user'   => 'مستخدم',
            default  => 'النظام',
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

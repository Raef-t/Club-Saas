<?php

namespace Modules\NotificationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Resource لقائمة الإشعارات في لوحة الإدارة
 */
class AdminNotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title ?? 'بدون عنوان',
            'body'  => $this->body ? Str::limit($this->body, 100) : '',

            'sender' => [
                'type'         => $this->sender_type,
                'id'           => $this->sender_id,
                'display_name' => $this->getSenderDisplayName(),
            ],

            'distribution' => [
                'total_recipients'    => (int)($this->recipients_count ?? 0),
                'read_count'          => (int)($this->read_count ?? 0),
                'delivered_count'     => (int)($this->delivered_count ?? 0),
                'read_percentage'     => ($this->recipients_count > 0)
                    ? round(($this->read_count / $this->recipients_count) * 100, 1) : 0,
                'delivered_percentage' => ($this->recipients_count > 0)
                    ? round(($this->delivered_count / $this->recipients_count) * 100, 1) : 0,
            ],

            'attachments' => [
                'count' => $this->attachments ? $this->attachments->count() : 0,
                'files' => $this->attachments ? $this->attachments->map(fn($a) => [
                    'id'             => $a->id,
                    'name'           => $a->file_name ?? 'ملف غير معروف',
                    'size_formatted' => $this->formatBytes($a->size ?? 0),
                ])->values() : [],
            ],

            'created_at'       => $this->created_at instanceof \Carbon\Carbon
                ? $this->created_at->toDateTimeString()
                : (is_string($this->created_at) ? $this->created_at : null),
            'created_at_human' => $this->created_at instanceof \Carbon\Carbon
                ? $this->created_at->diffForHumans()
                : 'منذ فترة',

            'target_snapshot' => $this->target_snapshot,
            'status'          => $this->getStatus(),
        ];
    }

    private function getSenderDisplayName(): string
    {
        return match ($this->sender_type) {
            'admin'  => 'الإدارة',
            'system' => 'النظام',
            'user'   => 'مستخدم',
            default  => 'غير معروف',
        };
    }

    private function getStatus(): string
    {
        if ($this->recipients_count == 0) return 'no_recipients';
        if ($this->delivered_count >= $this->recipients_count * 0.9) return 'delivered';
        if ($this->delivered_count < $this->recipients_count * 0.1) return 'pending';
        return 'partial';
    }

    private function formatBytes($bytes, $precision = 1): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

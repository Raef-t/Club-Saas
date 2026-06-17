<?php

namespace Modules\NotificationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class NotificationLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'recipient' => [
                'id' => $this->recipient_id,
                'type' => class_basename($this->recipient_type),
            ],
            'channel' => $this->channel,
            'type' => $this->resolveType(),
            'type_label' => $this->subject,
            'subject' => $this->subject,
            'body' => $this->content,
            'content' => $this->content,
            'status' => $this->status,
            'is_read' => $this->read_at !== null,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_time' => $this->created_at ? Carbon::parse($this->created_at)->diffForHumans() : null,
            'sent_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Infer notification type from subject/content for UI styling.
     */
    protected function resolveType(): string
    {
        $subject = strtolower($this->subject ?? '');
        $content = strtolower($this->content ?? '');
        $combined = $subject . ' ' . $content;

        if (str_contains($combined, 'offer') || str_contains($combined, 'عرض') || str_contains($combined, 'خصم')) {
            return 'offer';
        }
        if (str_contains($combined, 'warning') || str_contains($combined, 'سينتهي') || str_contains($combined, 'expir')) {
            return 'warning';
        }
        if (str_contains($combined, 'alert') || str_contains($combined, 'متأخر') || str_contains($combined, 'overdue')) {
            return 'alert';
        }
        if (str_contains($combined, 'success') || str_contains($combined, 'تم') || str_contains($combined, 'confirm')) {
            return 'success';
        }

        return 'info';
    }
}

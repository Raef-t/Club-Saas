<?php

namespace Modules\NotificationManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'sent_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

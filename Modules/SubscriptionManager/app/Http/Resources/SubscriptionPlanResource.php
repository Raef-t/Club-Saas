<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'duration_days' => $this->duration_days,
            'session_count' => $this->session_count,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
        ];
    }
}

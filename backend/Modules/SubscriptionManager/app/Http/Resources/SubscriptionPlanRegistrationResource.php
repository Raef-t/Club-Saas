<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanRegistrationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'duration_days' => $this->duration_days,
            'session_count' => $this->session_count,
            'max_freeze_count' => $this->max_freeze_count,
            'max_freeze_days' => $this->max_freeze_days,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'activities' => SubscriptionPlanActivityResource::collection($this->whenLoaded('planActivities')),
        ];
    }
}

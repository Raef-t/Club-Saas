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
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'duration_days' => $this->duration_days,
            'session_count' => $this->session_count,
            'sessions_per_week' => $this->sessions_per_week,
            'base_price' => $this->base_price,
            'max_subscribers' => $this->max_subscribers,
            'current_subscribers' => $this->current_subscribers,
            'is_unlimited_subscribers' => $this->max_subscribers == 0,
            'is_active' => (bool) $this->is_active,
            'activities' => SubscriptionPlanActivityResource::collection($this->whenLoaded('planActivities')),
            'session_templates' => $this->whenLoaded('sessionTemplates'),
        ];
    }
}

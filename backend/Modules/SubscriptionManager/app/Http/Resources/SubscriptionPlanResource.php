<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'subscription_number' => $this->subscription_number,
            'name' => $this->name,
            'type' => $this->type,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'duration_days' => $this->duration_days,
            'session_count' => $this->session_count,
            'base_price' => $this->base_price,
            'max_subscribers' => $this->max_subscribers,
            'current_subscribers' => $this->current_subscribers,
            'is_unlimited_subscribers' => $this->max_subscribers == 0,
            'activities' => SubscriptionPlanActivityResource::collection($this->whenLoaded('planActivities')),
            'session_templates' => $this->whenLoaded('sessionTemplates'),
        ];
    }
}

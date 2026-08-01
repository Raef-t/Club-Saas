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
            'session_count' => $this->session_count,
            'sessions_per_week' => $this->sessions_per_week,
            'base_price' => $this->base_price,
            'max_subscribers' => $this->max_subscribers,
            'current_subscribers' => $this->current_subscribers,
            'is_unlimited_subscribers' => $this->max_subscribers == 0,
            'gender_restriction' => $this->gender_restriction,
            'is_active' => (bool) $this->is_active,
            'activities' => SubscriptionPlanActivityResource::collection($this->whenLoaded('planActivities')),
            'session_templates' => $this->whenLoaded('sessionTemplates'),
        ];
    }
}

<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'activity_id' => $this->activity_id,
            'sessions_count' => $this->sessions_count,
            'is_unlimited' => $this->is_unlimited,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

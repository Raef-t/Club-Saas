<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\MemberManager\Http\Resources\MemberResource;

class PlayerSubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'member' => new MemberResource($this->whenLoaded('member')),
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'remaining_sessions' => $this->remaining_sessions,
            'paid_amount' => $this->paid_amount,
            'freezes' => $this->whenLoaded('freezes'),
        ];
    }
}

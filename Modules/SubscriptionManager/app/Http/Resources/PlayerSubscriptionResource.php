<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlayerSubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'member' => $this->member ? [
                'id' => $this->member->id,
                'member_number' => $this->member->memberNumber,
                'membership_status' => $this->member->status,
                'person' => $this->member->person ? [
                    'full_name' => $this->member->person->fullName,
                    'email' => $this->member->person->email,
                    'phone' => $this->member->person->mobile1,
                ] : null,
            ] : null,
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

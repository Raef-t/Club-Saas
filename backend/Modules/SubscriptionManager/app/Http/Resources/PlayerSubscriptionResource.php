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
                    'full_name' => $this->member->person->full_name,
                    'email' => $this->member->person->email,
                    'phone' => $this->member->person->mobile1,
                ] : null,
            ] : null,
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'coach_id' => $this->coach_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'remaining_sessions' => $this->remaining_sessions,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'activity_id' => $item->activity_id,
                        'activity' => $item->relationLoaded('activity') && $item->activity ? [
                            'id' => $item->activity->id,
                            'name' => $item->activity->name,
                        ] : null,
                        'coach_id' => $item->coach_id,
                        'coach' => $item->relationLoaded('coach') && $item->coach ? [
                            'id' => $item->coach->id,
                            'name' => $item->coach->person ? $item->coach->person->full_name : null,
                        ] : null,
                        'sessions_allocated' => $item->sessions_allocated,
                        'sessions_consumed' => $item->sessions_consumed ?? 0,
                        'is_unlimited' => (bool) $item->is_unlimited,
                    ];
                });
            }),
            'freezes' => $this->whenLoaded('freezes'),
        ];
    }
}

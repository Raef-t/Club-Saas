<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlayerSubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->plan ? $this->plan->branch_id : null,
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
            'months_count' => $this->months_count ?? 1,
            'start_date' => $this->start_date ? (\Illuminate\Support\Carbon::parse($this->start_date)->format('Y-m-d')) : null,
            'end_date' => $this->end_date ? (\Illuminate\Support\Carbon::parse($this->end_date)->format('Y-m-d')) : null,
            'status' => $this->status instanceof \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus ? $this->status->label() : $this->status,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'notes' => $this->notes,
            'reason' => $this->reason,
            'items' => $this->whenLoaded('items', function () {
                $planActivities = $this->plan && $this->plan->relationLoaded('planActivities')
                    ? $this->plan->planActivities
                    : collect();

                return $this->items->values()->map(function ($item, $index) use ($planActivities) {
                    $planActivity = $planActivities->get($index);
                    $staffActivity = $planActivity?->staffActivity;
                    $activity = $staffActivity?->activity;
                    $coach = $staffActivity?->staff;

                    return [
                        'id' => $item->id,
                        'activity_id' => $activity?->id,
                        'activity' => $activity ? [
                            'id' => $activity->id,
                            'name' => $activity->name,
                        ] : null,
                        'coach_id' => $coach?->id,
                        'coach' => $coach ? [
                            'id' => $coach->id,
                            'name' => $coach->person ? $coach->person->full_name : null,
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

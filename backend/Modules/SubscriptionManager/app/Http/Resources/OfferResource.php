<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray($request)
    {
        $plans = $this->relationLoaded('plans') ? $this->plans : $this->plans()->get();
        $availableSlots = null;

        if ($plans && $plans->isNotEmpty()) {
            $limitedPlans = $plans->filter(fn($p) => (int) $p->max_subscribers > 0);
            if ($limitedPlans->isNotEmpty()) {
                $availableSlots = (int) $limitedPlans->min(fn($p) => max(0, (int) $p->max_subscribers - (int) $p->current_subscribers));
            }
        }

        return [
            'id'              => $this->id,
            'branch_id'       => $this->branch_id,
            'name'            => $this->name,
            'description'     => $this->description,
            'price'           => (float) $this->price,
            'start_date'      => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date'        => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'is_active'       => (bool) $this->is_active,
            'available_slots' => $availableSlots,
            'is_available'    => (bool) $this->is_active && ($availableSlots === null || $availableSlots > 0),
            'plans'           => SubscriptionPlanResource::collection($this->whenLoaded('plans')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}

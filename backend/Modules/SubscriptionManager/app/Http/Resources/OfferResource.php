<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray($request)
    {
        $plans = $this->relationLoaded('plans') ? $this->plans : $this->plans()->get();
        $offerType = $this->offer_type ?? 'bundle';
        $isBundle = $offerType === 'bundle';
        $availableSlots = null;
        $isCapacityAvailable = true;
        $isDateValid = method_exists($this->resource, 'isDateValid') ? $this->isDateValid() : true;

        if ($plans && $plans->isNotEmpty()) {
            if ($isBundle) {
                $hasFullPlan = false;
                $limitedSlots = [];
                foreach ($plans as $p) {
                    $max = (int) $p->max_subscribers;
                    $current = method_exists($p, 'getCurrentSubscribersCount') ? $p->getCurrentSubscribersCount() : (int) $p->current_subscribers;
                    if ($max > 0) {
                        $remaining = max(0, $max - $current);
                        $limitedSlots[] = $remaining;
                        if ($remaining <= 0) {
                            $hasFullPlan = true;
                        }
                    }
                }
                if ($hasFullPlan) {
                    $isCapacityAvailable = false;
                    $availableSlots = 0;
                } elseif (!empty($limitedSlots)) {
                    $availableSlots = min($limitedSlots);
                }
            } else {
                $totalAvailableSlots = 0;
                $hasLimitedPlan = false;
                $hasAvailablePlan = false;

                foreach ($plans as $p) {
                    $max = (int) $p->max_subscribers;
                    $current = method_exists($p, 'getCurrentSubscribersCount') ? $p->getCurrentSubscribersCount() : (int) $p->current_subscribers;
                    if ($max > 0) {
                        $hasLimitedPlan = true;
                        $remaining = max(0, $max - $current);
                        $totalAvailableSlots += $remaining;
                        if ($remaining > 0) {
                            $hasAvailablePlan = true;
                        }
                    } else {
                        $hasAvailablePlan = true;
                    }
                }

                $isCapacityAvailable = $hasAvailablePlan;
                if ($hasLimitedPlan) {
                    $availableSlots = $totalAvailableSlots;
                }
            }
        }

        return [
            'id'              => $this->id,
            'branch_id'       => $this->branch_id,
            'name'            => $this->name,
            'description'     => $this->description,
            'offer_type'      => $offerType,
            'price'           => (float) $this->price,
            'start_date'      => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date'        => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'is_active'       => (bool) $this->is_active,
            'available_slots' => $availableSlots,
            'is_available'    => (bool) $this->is_active && $isDateValid && $isCapacityAvailable,
            'active_subscribers_count' => $this->relationLoaded('subscriptions')
                ? $this->subscriptions->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value)->count()
                : $this->subscriptions()->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value)->count(),
            'branch'          => $this->relationLoaded('branch') && $this->branch ? [
                'id'   => $this->branch->id,
                'name' => $this->branch->name,
            ] : null,
            'plans'           => SubscriptionPlanResource::collection($this->whenLoaded('plans')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}

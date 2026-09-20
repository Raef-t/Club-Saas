<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;
use Modules\SubscriptionManager\Models\SubscriptionPlan;

/**
 * @mixin SubscriptionPlan
 * @property-read SubscriptionPlan $resource
 */
class SubscriptionPlanRegistrationResource extends JsonResource
{
    public function toArray($request): array
    {
        $targetDate = $request && ($request->filled('date') || $request->filled('target_date'))
            ? Carbon::parse($request->input('date') ?? $request->input('target_date'))
            : null;

        $currentSubscribers = method_exists($this->resource, 'getCurrentSubscribersCount')
            ? $this->resource->getCurrentSubscribersCount($targetDate)
            : (int) ($this->resource->current_subscribers ?? $this->current_subscribers ?? 0);

        $isPlanActive = $this->status instanceof SubscriptionPlanStatus
            ? $this->status === SubscriptionPlanStatus::ACTIVE
            : $this->status === 'active';

        $isAvailable = $isPlanActive && ($this->max_subscribers == 0 || $currentSubscribers < (int) $this->max_subscribers);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'session_count' => $this->session_count,
            'sessions_per_week' => $this->sessions_per_week,
            'base_price' => $this->base_price,
            'coach_price' => $this->coach_price,
            'branch_price' => $this->branch_price,
            'currency' => $this->currency ?? 'SYP',
            'currency_type' => $this->currency ?? 'SYP',
            'max_subscribers' => $this->max_subscribers,
            'current_subscribers' => $currentSubscribers,
            'is_unlimited_subscribers' => (bool) ($this->is_unlimited_subscribers ?? ($this->max_subscribers == 0)),
            'is_available' => $isAvailable,
            'available_slots' => $this->max_subscribers > 0 ? max(0, (int) $this->max_subscribers - $currentSubscribers) : null,
            'gender_restriction' => $this->gender_restriction,
            'status' => $this->status instanceof SubscriptionPlanStatus ? $this->status->value : $this->status,
            'activities' => SubscriptionPlanActivityResource::collection($this->whenLoaded('planActivities')),
            'activity_types' => $this->whenLoaded('planActivities', function () {
                return $this->planActivities
                    ->map(function ($planAct) {
                        $activity = $planAct->staffActivity?->activity;
                        $type = $activity?->activityType;
                        if (!$type) {
                            return null;
                        }
                        return [
                            'id' => $type->id,
                            'name' => $type->name,
                        ];
                    })
                    ->filter()
                    ->unique('id')
                    ->values();
            }),
            'session_templates' => $this->whenLoaded('sessionTemplates'),
        ];
    }
}
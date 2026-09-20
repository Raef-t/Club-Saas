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
class SubscriptionPlanResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'subscription_number' => $this->subscription_number,
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
            'is_private_equipment' => (bool) (method_exists($this->resource, 'isPrivateEquipmentPlan') ? $this->isPrivateEquipmentPlan() : false),
            'is_session_based' => (bool) (method_exists($this->resource, 'isGroupSessionPlan') ? $this->isGroupSessionPlan() : false),
            'reason' => $this->reason,
            'is_suspended' => $this->relationLoaded('activeSuspension') 
                ? $this->activeSuspension !== null 
                : $this->suspensions()->whereIn('status', ['active', 'scheduled'])->exists(),
            'active_suspension' => $this->when($this->relationLoaded('activeSuspension') ? $this->activeSuspension !== null : $this->suspensions()->whereIn('status', ['active', 'scheduled'])->exists(), function () {
                $suspension = $this->relationLoaded('activeSuspension') 
                    ? $this->activeSuspension 
                    : $this->suspensions()->whereIn('status', ['active', 'scheduled'])->latest()->first();

                if (!$suspension) {
                    return null;
                }

                return [
                    'id' => $suspension->id,
                    'suspend_start_date' => $suspension->suspend_start_date ? \Carbon\Carbon::parse($suspension->suspend_start_date)->format('Y-m-d') : null,
                    'suspend_end_date' => $suspension->suspend_end_date ? \Carbon\Carbon::parse($suspension->suspend_end_date)->format('Y-m-d') : null,
                    'actual_end_date' => $suspension->actual_end_date ? \Carbon\Carbon::parse($suspension->actual_end_date)->format('Y-m-d') : null,
                    'suspension_days' => $suspension->suspension_days,
                    'reason' => $suspension->reason,
                    'status' => $suspension->status,
                    'coach_id' => $suspension->coach_id,
                    'coach_name' => $suspension->coach?->person?->full_name,
                    'affected_subscribers_count' => $suspension->affected_subscribers_count,
                ];
            }),
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

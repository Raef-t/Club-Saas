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
            'club_commission_percentage' => $this->club_commission_percentage,
            'coach_commission_percentage' => $this->coach_commission_percentage,
            'max_subscribers' => $this->max_subscribers,
            'current_subscribers' => $this->current_subscribers,
            'is_unlimited_subscribers' => (bool) ($this->is_unlimited_subscribers ?? ($this->max_subscribers == 0)),
            'gender_restriction' => $this->gender_restriction,
            'status' => $this->status instanceof \Modules\SubscriptionManager\Enums\SubscriptionPlanStatus ? $this->status->value : $this->status,
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
            'session_templates' => $this->whenLoaded('sessionTemplates'),
        ];
    }
}

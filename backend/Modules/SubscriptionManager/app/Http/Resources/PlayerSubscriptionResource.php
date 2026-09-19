<?php

namespace Modules\SubscriptionManager\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlayerSubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        $member = $this->member;
        if (!$member && !empty($this->member_id)) {
            $member = \Modules\MemberManager\Models\Member::with(['person.user', 'person.contacts'])->find($this->member_id);
        }

        return [
            'id' => $this->id,
            'branch_id' => $this->plan ? $this->plan->branch_id : null,
            'member' => $this->resolveMemberData($member),
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'months_count' => $this->months_count ?? 1,
            'start_date' => $this->start_date ? (\Illuminate\Support\Carbon::parse($this->start_date)->format('Y-m-d')) : null,
            'end_date' => $this->end_date ? (\Illuminate\Support\Carbon::parse($this->end_date)->format('Y-m-d')) : null,
            'status' => $this->status instanceof \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus ? $this->status->value : $this->status,
            'status_label' => $this->status instanceof \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus ? $this->status->label() : $this->status,
            'is_expiring_soon' => method_exists($this->resource, 'isExpiringSoon') ? $this->isExpiringSoon() : false,
            'total_amount' => $this->total_amount,
            'original_total_amount' => round((float) $this->total_amount + (float) ($this->discount_amount ?? 0), 2),
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'is_discount' => (bool) ($this->is_discount ?? false),
            'discount_percentage' => $this->discount_percentage ?? 0,
            'coach_discount_percentage' => $this->coach_discount_percentage ?? 0,
            'branch_discount_percentage' => $this->branch_discount_percentage ?? 0,
            'discount_amount' => $this->discount_amount ?? 0,
            'discount_reason' => $this->discount_reason,
            'currency' => $this->currency ?? ($this->plan?->currency ?? 'SYP'),
            'currency_type' => $this->currency ?? ($this->plan?->currency ?? 'SYP'),
            'notes' => $this->notes,
            'reason' => $this->reason,
            'created_by' => $this->created_by ? [
                'id' => $this->created_by,
                'name' => $this->creator?->person?->full_name ?? $this->creator?->username ?? null,
            ] : null,
            'receipt_number' => $this->relationLoaded('payments') && $this->payments->isNotEmpty()
                ? $this->payments->sortByDesc('id')->first()?->receipt_number
                : ($this->branch_receipt_number ?? null),
            'coach_receipt_number' => $this->coach_receipt_number ?? ($this->relationLoaded('revenueSplit') ? $this->revenueSplit?->coach_receipt_number : null),
            'branch_receipt_number' => $this->branch_receipt_number ?? ($this->relationLoaded('revenueSplit') ? $this->revenueSplit?->branch_receipt_number : null),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
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
            'freezes' => $this->whenLoaded('freezes', function () {
                return $this->freezes->map(function ($freeze) {
                    return [
                        'id' => $freeze->id,
                        'player_subscription_id' => $freeze->player_subscription_id,
                        'subscription_plan_suspension_id' => $freeze->subscription_plan_suspension_id,
                        'freeze_start_date' => $freeze->freeze_start_date ? \Illuminate\Support\Carbon::parse($freeze->freeze_start_date)->format('Y-m-d') : null,
                        'freeze_end_date' => $freeze->freeze_end_date ? \Illuminate\Support\Carbon::parse($freeze->freeze_end_date)->format('Y-m-d') : null,
                        'actual_end_date' => $freeze->actual_end_date ? \Illuminate\Support\Carbon::parse($freeze->actual_end_date)->format('Y-m-d') : null,
                        'reason' => $freeze->reason,
                        'freeze_days' => $freeze->freeze_days,
                    ];
                });
            }),
            'revenue_split' => $this->relationLoaded('revenueSplit') && $this->revenueSplit ? [
                'id' => $this->revenueSplit->id,
                'coach_id' => $this->revenueSplit->coach_id,
                'total_amount' => $this->revenueSplit->total_amount,
                'currency' => $this->revenueSplit->currency ?? ($this->currency ?? 'SYP'),
                'currency_type' => $this->revenueSplit->currency ?? ($this->currency ?? 'SYP'),
                'club_percentage' => $this->revenueSplit->club_percentage,
                'coach_percentage' => $this->revenueSplit->coach_percentage,
                'club_amount' => $this->revenueSplit->club_amount,
                'coach_amount' => $this->revenueSplit->coach_amount,
                'coach_receipt_number' => $this->revenueSplit->coach_receipt_number,
                'branch_receipt_number' => $this->revenueSplit->branch_receipt_number,
            ] : null,
        ];
    }

    protected function resolveMemberData($member): ?array
    {
        if (!$member) {
            return null;
        }

        if ($member instanceof \Modules\Core\DTOs\MemberDTO) {
            $person = $member->person;
            $username = $member->username ?? $person?->username ?? null;
            $customUsername = $member->customUsername ?? $person?->customUsername ?? null;

            if ($username === null && !empty($member->personId)) {
                $user = \Modules\Authentication\Models\User::where('person_id', $member->personId)->first();
                $username = $user?->username ?? null;
                $customUsername = $user?->custom_username ?? null;
            }

            return [
                'id' => $member->id,
                'member_number' => $member->memberNumber,
                'membership_status' => $member->status,
                'status' => $member->status,
                'is_active' => $member->isActive,
                'username' => $username,
                'custom_username' => $customUsername,
                'custom_user_name' => $customUsername,
                'generated_username' => $username,
                'person' => $person ? [
                    'full_name' => $person->fullName,
                    'email' => $person->email,
                    'phone' => $person->mobile1,
                    'username' => $username,
                    'custom_username' => $customUsername,
                    'custom_user_name' => $customUsername,
                    'generated_username' => $username,
                ] : null,
            ];
        }

        $person = $member->person;
        $status = $member->membership_status ?? ($member->status ?? null);
        $user = $person?->user ?? (!empty($member->person_id) ? \Modules\Authentication\Models\User::where('person_id', $member->person_id)->first() : null);
        $username = $user?->username ?? null;
        $customUsername = $user?->custom_username ?? null;

        return [
            'id' => $member->id,
            'member_number' => $member->member_number ?? ($member->memberNumber ?? null),
            'membership_status' => $status,
            'status' => $status,
            'is_active' => $member->is_active ?? ($status === 'active'),
            'username' => $username,
            'custom_username' => $customUsername,
            'custom_user_name' => $customUsername,
            'generated_username' => $username,
            'person' => $person ? [
                'full_name' => $person->full_name ?? ($person->fullName ?? null),
                'email' => $person->email,
                'phone' => $person->contacts?->first()?->phone_number ?? ($person->mobile1 ?? null),
                'username' => $username,
                'custom_username' => $customUsername,
                'custom_user_name' => $customUsername,
                'generated_username' => $username,
            ] : null,
        ];
    }
}

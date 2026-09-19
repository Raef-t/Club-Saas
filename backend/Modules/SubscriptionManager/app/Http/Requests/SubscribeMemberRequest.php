<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Modules\SubscriptionManager\Domain\Rules\NoActiveSubscriptionRule;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;

class SubscribeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('is_discount')) {
            $this->merge(['is_discount' => filter_var($this->input('is_discount'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false]);
        }

        $discountPercentage = $this->input('discount_percentage');
        if ($this->filled('discount_percentage') && !is_numeric($discountPercentage)) {
            $discountPercentage = 0;
        }

        if ($this->filled('discount_percentage')) {
            $discountPercentage = (float) $discountPercentage;
            if ($discountPercentage < 0) {
                $discountPercentage = 0;
            }
            if ($discountPercentage > 100) {
                $discountPercentage = 100;
            }
            $this->merge(['discount_percentage' => $discountPercentage]);
        }

        if ($this->filled('coach_discount_percentage')) {
            $coachDiscount = max(0, min(100, (float) $this->input('coach_discount_percentage')));
            $this->merge(['coach_discount_percentage' => $coachDiscount]);
        }

        if ($this->filled('branch_discount_percentage')) {
            $branchDiscount = max(0, min(100, (float) $this->input('branch_discount_percentage')));
            $this->merge(['branch_discount_percentage' => $branchDiscount]);
        }

        if ($this->filled('discount_amount')) {
            $this->merge(['discount_amount' => max(0, (float) $this->input('discount_amount'))]);
        }

        if ($this->filled('discount_reason') && !is_string($this->input('discount_reason'))) {
            $this->merge(['discount_reason' => (string) $this->input('discount_reason')]);
        }
    }

    public function rules(): array
    {
        $subscriptionRepo = app(PlayerSubscriptionRepositoryInterface::class);

        return [
            'member_id' => [
                'required',
                'exists:members,id',
                new NoActiveSubscriptionRule($subscriptionRepo, $this->member_id, $this->plan_id, $this->input('start_date'))
            ],
            'plan_id' => 'required|exists:subscription_plans,id',
            'months_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'paid_amount' => 'required|numeric|min:0',
            'coach_paid_amount' => 'nullable|numeric|min:0',
            'branch_paid_amount' => 'nullable|numeric|min:0',
            'coach_price' => 'nullable|numeric|min:0',
            'branch_price' => 'nullable|numeric|min:0',
            'is_discount' => 'nullable|boolean',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'coach_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'branch_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:100',
            'coach_receipt_number' => 'nullable|string|max:100',
            'branch_receipt_number' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
        ];
    }
}

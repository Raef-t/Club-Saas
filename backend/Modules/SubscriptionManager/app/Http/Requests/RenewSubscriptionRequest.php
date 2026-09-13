<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'coach_id' => 'nullable|exists:staff,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'receipt_number' => 'nullable|string|max:100',
            'coach_receipt_number' => 'nullable|string|max:100',
            'branch_receipt_number' => 'nullable|string|max:100',
            'coach_paid_amount' => 'nullable|numeric|min:0',
            'branch_paid_amount' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'months_count' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

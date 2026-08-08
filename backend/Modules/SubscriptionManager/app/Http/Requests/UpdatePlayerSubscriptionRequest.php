<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => 'nullable|exists:members,id',
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'offer_id' => 'nullable|exists:offers,id',
            'months_count' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:active,finished,frozen,terminated,expired,cancelled',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}

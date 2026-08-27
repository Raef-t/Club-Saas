<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeOfferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'member_id' => 'required|exists:members,id',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,wallet,bank_transfer',
            'receipt_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'duration_days' => 'nullable|integer|min:1',
            'months_count' => 'nullable|integer|min:1',
        ];
    }
}

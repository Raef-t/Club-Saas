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
            'notes' => 'nullable|string',
            'start_date' => 'nullable|date',

        ];
    }
}

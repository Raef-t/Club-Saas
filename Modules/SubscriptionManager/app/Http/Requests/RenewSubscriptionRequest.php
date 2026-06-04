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
            'coach_id' => 'nullable|integer',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

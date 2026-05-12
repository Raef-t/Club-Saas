<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FreezeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'freeze_start_date' => 'required|date',
            'freeze_end_date' => 'required|date|after:freeze_start_date',
            'reason' => 'nullable|string|max:500',
        ];
    }
}

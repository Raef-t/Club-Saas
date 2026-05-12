<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|array',
            'name.ar' => 'required|string|max:150',
            'name.en' => 'nullable|string|max:150',
            'type' => 'required|in:fixed_period,session_based',
            'duration_days' => 'required_if:type,fixed_period|nullable|integer|min:1',
            'session_count' => 'required_if:type,session_based|nullable|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}

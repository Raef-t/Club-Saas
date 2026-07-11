<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_session_based' => 'boolean',
            'has_unlimited_subscribers' => 'boolean',
            'has_shifts' => 'boolean',
        ];
    }
}

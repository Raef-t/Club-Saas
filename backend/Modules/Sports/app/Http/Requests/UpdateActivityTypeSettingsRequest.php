<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityTypeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_session_based' => 'boolean',
            'has_unlimited_subscribers' => 'boolean',
            'has_shifts' => 'boolean',
            'is_daily_entry' => 'boolean',
        ];
    }
}

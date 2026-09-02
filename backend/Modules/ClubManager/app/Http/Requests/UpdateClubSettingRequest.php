<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'theme_colors' => 'sometimes|array',
            'language' => 'sometimes|string|in:ar,en,all',
            'allowed_debt_limit' => 'nullable|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0',
            'enabled_features' => 'nullable|array',
            'bg_image_url' => 'nullable|string|url',
        ];
    }
}

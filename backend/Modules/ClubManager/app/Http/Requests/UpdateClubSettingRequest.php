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
            'allow_partial_payment' => 'sometimes|boolean',
            'enabled_features' => 'sometimes|array',
            'bg_image_url' => 'sometimes|string|nullable'
        ];
    }
}

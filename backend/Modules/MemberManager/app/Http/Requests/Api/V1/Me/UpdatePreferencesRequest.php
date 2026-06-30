<?php

namespace Modules\MemberManager\Http\Requests\Api\V1\Me;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => 'sometimes|string|in:ar,en',
            'notifications_enabled' => 'sometimes|boolean',
        ];
    }
}

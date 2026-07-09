<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'sometimes|exists:branches,id',
            'duration_minutes' => 'sometimes|nullable|integer|min:1',
            'activity_type_id' => 'nullable|exists:activity_types,id',
            'is_private_equipment' => 'nullable|boolean',
            'gender_allowed' => 'nullable|in:male,female,mixed',
            'is_active' => 'nullable|boolean',
        ];
    }
}

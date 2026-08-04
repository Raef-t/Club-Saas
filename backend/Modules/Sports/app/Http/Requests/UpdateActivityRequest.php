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
            'name' => 'sometimes|string|max:150',
            'description' => 'nullable|string',
            'activity_type_id' => 'nullable|exists:activity_types,id',
            'is_private_equipment' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }
}

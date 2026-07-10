<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'is_private_equipment' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'gender_allowed' => 'nullable|in:male,female,mixed',
        ];
    }
}

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
            'name' => 'required|array',
            'name.ar' => 'required|string|max:150',
            'name.en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'activity_type_id' => 'required|exists:activity_types,id',
            'default_capacity' => 'nullable|integer|min:1',
            'is_private_equipment' => 'nullable|boolean',
            'gender_allowed' => 'nullable|in:male,female,mixed',
        ];
    }
}

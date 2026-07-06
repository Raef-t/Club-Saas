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
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}

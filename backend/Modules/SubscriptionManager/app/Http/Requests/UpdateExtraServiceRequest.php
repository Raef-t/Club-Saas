<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExtraServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'default_price' => 'sometimes|required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}

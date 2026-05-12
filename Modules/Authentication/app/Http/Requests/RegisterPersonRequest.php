<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['player', 'coach', 'staff'])],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'dob' => 'nullable|date',
            'mobile_1' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
        ];
    }
}

<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Map 'new_password_confirmation' from the contract field name.
     */
    public function messages(): array
    {
        return [
            'new_password.confirmed' => __('The new password confirmation does not match.'),
        ];
    }
}

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
            'user_id' => 'nullable|integer|exists:authentication_users,id',
            'new_password' => 'required|string|min:6|confirmed',
            'custom_username' => ['nullable', 'string'],
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

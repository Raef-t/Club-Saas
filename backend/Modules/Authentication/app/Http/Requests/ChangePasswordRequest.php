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
            'custom_username' => ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9\p{Arabic}](?:[a-zA-Z0-9\p{Arabic}_.-]{1,28}[a-zA-Z0-9\p{Arabic}])?$/u'],
        ];
    }

    /**
     * Map 'new_password_confirmation' from the contract field name.
     */
    public function messages(): array
    {
        return [
            'new_password.confirmed' => __('The new password confirmation does not match.'),
            'custom_username.regex' => __('صيغة اسم المستخدم غير صالحة. يجب أن يتكون من 3-30 حرفاً (حروف، أرقام، _ . -).'),
            'custom_username.min' => __('يجب أن يتكون اسم المستخدم من 3 أحرف على الأقل.'),
            'custom_username.max' => __('يجب ألا يتجاوز اسم المستخدم 30 حرفاً.'),
        ];
    }
}

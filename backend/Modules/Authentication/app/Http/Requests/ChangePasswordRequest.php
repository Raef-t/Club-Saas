<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Modules\Authentication\Models\User;

class ChangePasswordRequest extends FormRequest
{
    public const DISALLOWED_PASSWORDS = [
        '12345678',
        '119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed', // sha256(secretKey + '12345678') from web client
        'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', // raw sha256('12345678')
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:authentication_users,id',
            'new_password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                function ($attribute, $value, $fail) {
                    if (in_array((string) $value, self::DISALLOWED_PASSWORDS, true)) {
                        $fail(__('لا يمكن استخدام كلمة المرور الافتراضية (12345678) ككلمة مرور جديدة.'));
                        return;
                    }

                    $userId = $this->input('user_id');
                    $user = $userId ? User::find($userId) : $this->user();

                    if ($user && $user->password) {
                        $isCurrentDefault = false;
                        foreach (self::DISALLOWED_PASSWORDS as $disallowed) {
                            if (Hash::check($disallowed, $user->password)) {
                                $isCurrentDefault = true;
                                break;
                            }
                        }

                        if ($isCurrentDefault && Hash::check($value, $user->password)) {
                            $fail(__('لا يمكن استخدام كلمة المرور الافتراضية (12345678) ككلمة مرور جديدة.'));
                        }
                    }
                },
            ],
            'custom_username' => ['nullable', 'string'],
        ];
    }

    /**
     * Map 'new_password_confirmation' from the contract field name.
     */
    public function messages(): array
    {
        return [
            'new_password.required'  => __('كلمة المرور الجديدة مطلوبة.'),
            'new_password.min'       => __('كلمة المرور يجب أن تتكون من 6 أحرف على الأقل.'),
            'new_password.confirmed' => __('The new password confirmation does not match.'),
        ];
    }
}


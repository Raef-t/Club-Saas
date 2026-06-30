<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'type' => 'required|in:player,coach,staff',
            'country_code' => 'nullable|string|max:5',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email',
            'username' => 'nullable|string|unique:authentication_users,username',
        ];
    }
}

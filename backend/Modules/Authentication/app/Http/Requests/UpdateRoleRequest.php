<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->where(function ($query) {
                    return $query->where('guard_name', 'sanctum');
                })->ignore($roleId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الدور مطلوب.',
            'name.unique'   => 'هذا الدور موجود مسبقاً في النظام.',
        ];
    }
}

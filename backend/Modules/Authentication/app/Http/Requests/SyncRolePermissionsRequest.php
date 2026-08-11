<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'قائمة الصلاحيات مطلوبة.',
            'permissions.array'    => 'يجب أن تكون الصلاحيات على شكل قائمة (array).',
            'permissions.*.exists' => 'إحدى الصلاحيات المدخلة غير موجودة في النظام.',
        ];
    }
}

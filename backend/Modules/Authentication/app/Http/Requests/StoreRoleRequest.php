<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:roles,name|regex:/^[a-z_]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الدور مطلوب.',
            'name.unique'   => 'هذا الدور موجود مسبقاً في النظام.',
            'name.regex'    => 'يجب أن يحتوي اسم الدور على حروف صغيرة وشرطة سفلية فقط (مثال: member_manager).',
        ];
    }
}

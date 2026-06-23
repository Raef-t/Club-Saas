<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'               => 'required|string|max:20|unique:acc_accounts,code',
            'name'               => 'required|string|max:150',
            'name_en'            => 'nullable|string|max:150',
            'type'               => 'required|in:asset,liability,equity,revenue,expense',
            'currency'           => 'nullable|in:USD,SYP,BOTH',
            'parent_id'          => 'nullable|integer|exists:acc_accounts,id',
            'is_active'          => 'nullable|boolean',
            'allow_manual_entry' => 'nullable|boolean',
            'description'        => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'  => 'رمز الحساب مطلوب',
            'code.unique'    => 'رمز الحساب مستخدم مسبقاً',
            'name.required'  => 'اسم الحساب مطلوب',
            'type.required'  => 'نوع الحساب مطلوب',
            'type.in'        => 'نوع الحساب يجب أن يكون: asset, liability, equity, revenue, expense',
            'currency.in'    => 'العملة يجب أن تكون: USD, SYP, BOTH',
            'parent_id.exists'=> 'الحساب الأب غير موجود',
        ];
    }
}

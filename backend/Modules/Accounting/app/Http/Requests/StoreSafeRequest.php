<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSafeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:100',
            'account_id'          => 'required|integer|exists:acc_accounts,id',
            'currency'            => 'required|in:USD,SYP',
            'responsible_user_id' => 'nullable|integer',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string',
            'branch_id'           => 'nullable|integer|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'اسم الصندوق مطلوب',
            'account_id.required' => 'الحساب المرتبط مطلوب',
            'account_id.exists'   => 'الحساب المحدد غير موجود',
            'currency.required'   => 'عملة الصندوق مطلوبة',
            'currency.in'         => 'العملة يجب أن تكون USD أو SYP',
        ];
    }
}

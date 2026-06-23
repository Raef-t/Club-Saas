<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCounterpartyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:150',
            'type'           => 'required|in:customer,vendor,employee,other',
            'ar_account_id'  => 'nullable|integer|exists:acc_accounts,id',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'reference_type' => 'nullable|string|max:100',
            'reference_id'   => 'nullable|integer',
            'notes'          => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الطرف مطلوب',
            'type.required' => 'نوع الطرف مطلوب',
            'type.in'       => 'نوع الطرف يجب أن يكون: customer, vendor, employee, other',
            'email.email'   => 'صيغة البريد الإلكتروني غير صحيحة',
        ];
    }
}

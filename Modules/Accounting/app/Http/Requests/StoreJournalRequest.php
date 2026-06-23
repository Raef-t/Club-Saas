<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'               => 'required|in:JV,RV,PV,SI,PI',
            'date'               => 'required|date',
            'description'        => 'required|string|max:500',
            'period_id'          => 'nullable|integer|exists:acc_periods,id',
            'counterparty_id'    => 'nullable|integer|exists:acc_counterparties,id',
            'safe_id'            => 'nullable|integer|exists:acc_safes,id',
            'exchange_rate'      => 'nullable|numeric|min:0',
            'source_type'        => 'nullable|string|max:100',
            'source_id'          => 'nullable|integer',
            'notes'              => 'nullable|string',
            'branch_id'          => 'nullable|integer|exists:branches,id',
            'lines'              => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer|exists:acc_accounts,id',
            'lines.*.debit_usd'  => 'nullable|numeric|min:0',
            'lines.*.credit_usd' => 'nullable|numeric|min:0',
            'lines.*.debit_syp'  => 'nullable|numeric|min:0',
            'lines.*.credit_syp' => 'nullable|numeric|min:0',
            'lines.*.memo'       => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'               => 'نوع السند مطلوب',
            'type.in'                     => 'نوع السند يجب أن يكون: JV, RV, PV, SI, PI',
            'date.required'               => 'تاريخ السند مطلوب',
            'description.required'        => 'وصف السند مطلوب',
            'lines.required'              => 'تفاصيل القيود مطلوبة',
            'lines.min'                   => 'يجب أن يحتوي السند على سطرين على الأقل',
            'lines.*.account_id.required' => 'الحساب مطلوب في كل سطر',
            'lines.*.account_id.exists'   => 'أحد الحسابات المحددة غير موجود',
        ];
    }
}

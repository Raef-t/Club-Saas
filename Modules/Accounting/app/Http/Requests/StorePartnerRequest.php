<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:100',
            'capital_account_id'  => 'nullable|integer|exists:acc_accounts,id',
            'drawings_account_id' => 'nullable|integer|exists:acc_accounts,id',
            'profit_share_pct'    => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                function ($attribute, $value, $fail) {
                    $currentSum = \Modules\Accounting\Models\AccPartner::where('is_active', true)->sum('profit_share_pct');
                    if ($currentSum + $value > 100) {
                        $fail('مجموع نسب الأرباح للشركاء النشطين لا يمكن أن يتجاوز 100%. النسبة المتبقية المتاحة هي: ' . (100 - $currentSum) . '%');
                    }
                }
            ],
            'joined_at'           => 'required|date',
            'is_active'           => 'nullable|boolean',
            'notes'               => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'اسم الشريك مطلوب',
            'capital_account_id.required' => 'حساب رأس المال مطلوب',
            'capital_account_id.exists'   => 'حساب رأس المال غير موجود',
            'profit_share_pct.required'   => 'نسبة الشريك من الأرباح مطلوبة',
            'profit_share_pct.min'        => 'نسبة الأرباح يجب أن تكون 0 أو أكثر',
            'profit_share_pct.max'        => 'نسبة الأرباح لا يمكن أن تتجاوز 100%',
            'joined_at.required'          => 'تاريخ انضمام الشريك مطلوب',
        ];
    }
}

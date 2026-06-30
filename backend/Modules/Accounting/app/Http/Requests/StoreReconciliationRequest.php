<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReconciliationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'safe_id'              => 'required|integer|exists:acc_safes,id',
            'period_id'            => 'required|integer|exists:acc_periods,id',
            'physical_balance_usd' => 'required|numeric',
            'physical_balance_syp' => 'required|numeric',
            'notes'                => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'safe_id.required'              => 'الصندوق مطلوب',
            'safe_id.exists'                => 'الصندوق المحدد غير موجود',
            'period_id.required'            => 'الفترة المحاسبية مطلوبة',
            'period_id.exists'              => 'الفترة المحاسبية غير موجودة',
            'physical_balance_usd.required' => 'الرصيد الفعلي بالدولار مطلوب',
            'physical_balance_syp.required' => 'الرصيد الفعلي بالليرة مطلوب',
        ];
    }
}

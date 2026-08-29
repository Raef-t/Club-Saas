<?php

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('date') && $this->has('payment_date')) {
            $this->merge(['date' => $this->input('payment_date')]);
        }
    }

    public function rules(): array
    {
        return [
            'staff_id'   => 'required|integer|exists:staff,id',
            'safe_id'    => 'required|integer|exists:acc_safes,id',
            'period_id'  => 'required|integer|exists:acc_periods,id',
            'payslip_id' => 'nullable|integer|exists:payslips,id',
            'amount'     => 'required|numeric|min:0.01',
            'date'       => 'required|date',
            'notes'      => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'staff_id.required'  => 'اسم الموظف أو الكادر مطلوب.',
            'staff_id.exists'    => 'الموظف أو الكادر المحدد غير موجود.',
            'safe_id.required'   => 'الصندوق مطلوب.',
            'safe_id.exists'     => 'الصندوق المحدد غير موجود.',
            'period_id.required' => 'الفترة المالية مطلوبة.',
            'period_id.exists'   => 'الفترة المالية المحددة غير موجودة.',
            'amount.required'    => 'المبلغ مطلوب.',
            'amount.numeric'     => 'المبلغ يجب أن يكون رقماً.',
            'amount.min'         => 'المبلغ يجب أن يكون أكبر من الصفر.',
            'date.required'      => 'تاريخ الصرف مطلوب.',
            'date.date'          => 'التاريخ غير صالح.',
        ];
    }
}

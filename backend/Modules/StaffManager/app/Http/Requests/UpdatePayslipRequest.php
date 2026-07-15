<?php
namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayslipRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'base_pay' => 'sometimes|numeric|min:0',
            'commission_pay' => 'sometimes|numeric|min:0',
            'deductions' => 'sometimes|numeric|min:0',
            'deduction_reason' => 'nullable|string|max:255',
            'bonuses' => 'sometimes|numeric|min:0',
            'bonus_reason' => 'nullable|string|max:255',
        ];
    }
}

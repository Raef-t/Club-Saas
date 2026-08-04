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
            'adjustments' => 'sometimes|array',
            'adjustments.*.type' => 'required_with:adjustments|in:bonus,deduction',
            'adjustments.*.amount' => 'required_with:adjustments|numeric|min:0',
            'adjustments.*.reason' => 'nullable|string',
        ];
    }
}

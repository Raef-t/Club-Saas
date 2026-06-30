<?php
namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayslipRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'staff_id' => 'required|integer',
            'payroll_run_id' => 'required|integer',
        ];
    }
}

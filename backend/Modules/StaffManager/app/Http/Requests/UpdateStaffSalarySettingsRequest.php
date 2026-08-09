<?php
namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffSalarySettingsRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'base_salary' => 'sometimes|numeric|min:0',
            'percentage' => 'sometimes|numeric|min:0|max:100',
        ];
    }
}

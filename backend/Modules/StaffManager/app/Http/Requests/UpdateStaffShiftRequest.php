<?php
namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffShiftRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [];
    }
}

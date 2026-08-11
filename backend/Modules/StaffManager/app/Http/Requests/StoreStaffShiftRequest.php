<?php
namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffShiftRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'staff_id' => 'required|integer|exists:staff,id',
            'branch_shift_id' => 'required|integer|exists:branch_shifts,id',
        ];
    }
}

<?php
namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'staff_id' => 'required|integer',
            'activity_id' => 'required|integer',
        ];
    }
}

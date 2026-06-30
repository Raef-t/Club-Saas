<?php
namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffActivityRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [];
    }
}

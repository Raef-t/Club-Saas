<?php
namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberMeasurementRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [];
    }
}

<?php
namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberMeasurementRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'member_id' => 'required|integer',
        ];
    }
}

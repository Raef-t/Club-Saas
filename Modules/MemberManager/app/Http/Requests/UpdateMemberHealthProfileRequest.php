<?php
namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberHealthProfileRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            // Add rules as needed
        ];
    }
}

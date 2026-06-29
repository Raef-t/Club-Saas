<?php
namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberHealthProfileRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules() {
        return [
            'allergies' => 'nullable|string',
            'organic_diseases' => 'nullable|string',
            'physical_injuries' => 'nullable|string',
            'medications' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_country_code' => 'nullable|string|max:5',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'sport_goal' => 'nullable|string',
            'fitness_level' => 'nullable|string',
        ];
    }
}

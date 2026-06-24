<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerHealthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'allergies' => 'nullable|string|max:255',
            'organic_diseases' => 'nullable|string|max:255',
            'physical_injuries' => 'nullable|string|max:255',
            'medications' => 'nullable|string|max:255',
            'blood_type' => 'nullable|string|max:10',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'sport_goal' => 'nullable|string|max:255',
            'fitness_level' => 'nullable|string|in:beginner,intermediate,advanced,professional',
        ];
    }
}

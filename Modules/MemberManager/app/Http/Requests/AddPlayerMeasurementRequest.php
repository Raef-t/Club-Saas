<?php

namespace Modules\MemberManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPlayerMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measurement_date' => 'nullable|date',
            'weight' => 'required|numeric|min:20|max:300',
            'height' => 'nullable|numeric|min:50|max:250',
            'body_fat_percentage' => 'nullable|numeric|min:1|max:100',
            'muscle_mass' => 'nullable|numeric|min:1|max:150',
            'waist_circumference' => 'nullable|numeric|min:10|max:200',
            'chest_circumference' => 'nullable|numeric|min:10|max:200',
            'thigh_circumference' => 'nullable|numeric|min:10|max:200',
            'arm_circumference' => 'nullable|numeric|min:5|max:100',
            'activity_level' => 'nullable|string|max:100',
            'bmi' => 'nullable|numeric|min:10|max:100',
        ];
    }
}

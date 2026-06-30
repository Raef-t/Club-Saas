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
            'neck_circumference' => 'nullable|numeric|min:10|max:100',
            'shoulder_circumference' => 'nullable|numeric|min:10|max:200',
            'chest_circumference' => 'nullable|numeric|min:10|max:200',
            'waist_circumference' => 'nullable|numeric|min:10|max:200',
            'hip_circumference' => 'nullable|numeric|min:10|max:200',
            'buttocks_circumference' => 'nullable|numeric|min:10|max:200',
            'right_thigh_mid' => 'nullable|numeric|min:10|max:150',
            'left_thigh' => 'nullable|numeric|min:10|max:150',
            'above_right_knee' => 'nullable|numeric|min:10|max:100',
            'above_left_knee' => 'nullable|numeric|min:10|max:100',
            'right_calf' => 'nullable|numeric|min:10|max:100',
            'left_calf' => 'nullable|numeric|min:10|max:100',
            'right_bicep' => 'nullable|numeric|min:5|max:100',
            'left_bicep' => 'nullable|numeric|min:5|max:100',
            'arm_circumference' => 'nullable|numeric|min:5|max:100',
            'activity_level' => 'nullable|string|max:100',
        ];
    }
}

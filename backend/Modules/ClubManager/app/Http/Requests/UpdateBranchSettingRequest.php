<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end' => 'nullable|date_format:H:i|after:working_hours_start',
            'default_club_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'default_coach_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'default_employee_salary' => 'nullable|numeric|min:0',
            'daily_entry_price' => 'nullable|numeric|min:0',
            'locker_price' => 'nullable|numeric|min:0',
            'allow_freeze' => 'nullable|boolean',
            'display_mixed_activities' => 'nullable|boolean',
        ];
    }
}

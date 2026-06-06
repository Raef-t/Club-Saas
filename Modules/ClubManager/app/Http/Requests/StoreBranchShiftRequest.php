<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'gender_allowed' => 'required|string|in:male,female,mixed',
        ];
    }
}

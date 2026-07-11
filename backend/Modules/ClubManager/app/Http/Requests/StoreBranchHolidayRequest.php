<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:weekly,specific_dates',
            'day_of_week' => 'required_if:type,weekly|nullable|integer|min:0|max:6',
            'start_date' => 'required_if:type,specific_dates|nullable|date',
            'end_date' => 'required_if:type,specific_dates|nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ];
    }
}

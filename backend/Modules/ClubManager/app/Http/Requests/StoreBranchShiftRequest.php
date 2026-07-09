<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_id' => [
                'required',
                'integer',
                Rule::exists('activities', 'id')->where(function ($query) {
                    $query->where('branch_id', $this->route('branch'))
                        ->whereIn('activity_type_id', function ($q) {
                            $q->select('id')->from('activity_types')->whereIn('name', ['حصة جماعية', 'تدريب خاص']);
                        });
                }),
            ],
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'gender_allowed' => 'required|string|in:male,female,mixed',
        ];
    }
}

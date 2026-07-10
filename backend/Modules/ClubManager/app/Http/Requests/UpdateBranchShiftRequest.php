<?php

namespace Modules\ClubManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => 'sometimes|integer|min:0|max:6',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'gender_allowed' => 'sometimes|string|in:male,female,mixed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->failed()) {
                // If the fields are not provided in the update, use the existing shift's values for the overlap check
                $shift = \Modules\ClubManager\Models\BranchShift::findOrFail($this->route('shift'));
                $dayOfWeek = $this->input('day_of_week', $shift->day_of_week);
                $startTime = $this->input('start_time', $shift->start_time);
                $endTime = $this->input('end_time', $shift->end_time);

                $overlap = \Modules\ClubManager\Models\BranchShift::where('branch_id', $this->route('branch'))
                    ->where('id', '!=', $this->route('shift'))
                    ->where('day_of_week', $dayOfWeek)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->exists();

                if ($overlap) {
                    $validator->errors()->add('start_time', __('يوجد تعارض في الوقت مع وردية أخرى في نفس اليوم للفرع.'));
                }
            }
        });
    }
}

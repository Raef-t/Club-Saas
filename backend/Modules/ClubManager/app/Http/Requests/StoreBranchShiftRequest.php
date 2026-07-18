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
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'gender_allowed' => 'required|string|in:male,female,mixed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->failed()) {
                $overlap = \Modules\ClubManager\Models\BranchShift::where('branch_id', $this->route('branch'))
                    ->where('start_time', '<', $this->input('end_time'))
                    ->where('end_time', '>', $this->input('start_time'))
                    ->exists();

                if ($overlap) {
                    $validator->errors()->add('start_time', __('يوجد تعارض في الوقت مع وردية أخرى في نفس اليوم للفرع.'));
                }
            }
        });
    }
}

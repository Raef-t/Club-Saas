<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_id' => 'nullable|exists:activities,id',
            'staff_id' => 'nullable|integer',
            'facility_id' => 'nullable|integer',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'max_players' => 'nullable|integer|min:1',
            'gender_allowed' => 'nullable|in:male,female,mixed',
            'status' => 'nullable|in:scheduled,cancelled,completed',
        ];
    }
}

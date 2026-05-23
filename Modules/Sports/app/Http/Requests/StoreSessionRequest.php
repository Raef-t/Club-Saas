<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|integer',
            'activity_id' => 'required|exists:activities,id',
            'staff_id' => 'nullable|integer',
            'facility_id' => 'nullable|integer',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'max_players' => 'nullable|integer|min:1',
            'gender_allowed' => 'nullable|in:male,female,mixed',
            'status' => 'nullable|in:scheduled,cancelled,completed',
        ];
    }
}

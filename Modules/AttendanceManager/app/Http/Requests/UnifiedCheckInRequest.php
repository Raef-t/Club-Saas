<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnifiedCheckInRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'attendable_type' => ['required', 'string', 'in:member,staff'],
            'attendable_id'   => ['required', 'integer'],
            'club_id'         => ['required', 'integer'],
            'branch_id'       => ['required', 'integer'],
            'facility_id'     => ['nullable', 'integer'],
            'check_in_at'     => ['nullable', 'date'],
            'metadata'        => ['nullable', 'array'],
        ];
    }
}

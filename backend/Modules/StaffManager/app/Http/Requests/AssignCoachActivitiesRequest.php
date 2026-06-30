<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCoachActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_ids'   => ['required', 'array'],
            'activity_ids.*' => ['exists:activities,id'],
        ];
    }
}

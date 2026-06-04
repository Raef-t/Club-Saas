<?php

namespace Modules\Sports\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetWeeklyScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|integer',
            'start_date' => 'nullable|date',
        ];
    }
}

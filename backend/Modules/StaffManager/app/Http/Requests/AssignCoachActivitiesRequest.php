<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCoachActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('activity_ids') && !is_array($this->activity_ids)) {
            $this->merge([
                'activity_ids' => is_string($this->activity_ids) && str_contains($this->activity_ids, ',') 
                    ? explode(',', $this->activity_ids) 
                    : [$this->activity_ids]
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'activity_ids'   => ['required', 'array'],
            'activity_ids.*' => ['exists:activities,id'],
        ];
    }
}

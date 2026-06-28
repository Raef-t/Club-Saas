<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specialization'          => ['nullable', 'string', 'max:255'],
            'bio'                     => ['nullable', 'string'],
            'experience_years'        => ['nullable', 'integer', 'min:0'],
            'working_hours_per_week'  => ['nullable', 'numeric', 'min:0'],
            'gym_type'                => ['nullable', 'string'],
            'payment_type'            => ['nullable', 'string'],
            'commission_type'         => ['nullable', 'string'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

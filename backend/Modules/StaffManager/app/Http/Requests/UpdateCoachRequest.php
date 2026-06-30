<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Info
            'base_salary'             => ['nullable', 'numeric', 'min:0'],
            'employment_type'         => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'shift_type'              => ['nullable', 'string'],
            'work_status'             => ['nullable', 'string'],
            'is_active'               => ['nullable', 'boolean'],
            'branch_id'               => ['nullable', 'exists:branches,id'],
            
            // Details
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

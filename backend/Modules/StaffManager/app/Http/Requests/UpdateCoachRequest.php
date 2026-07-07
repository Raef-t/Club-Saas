<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('branch_ids') && !is_array($this->branch_ids)) {
            $this->merge([
                'branch_ids' => is_string($this->branch_ids) && str_contains($this->branch_ids, ',') 
                    ? explode(',', $this->branch_ids) 
                    : [$this->branch_ids]
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Person Fields
            'first_name'              => ['nullable', 'string', 'max:255'],
            'last_name'               => ['nullable', 'string', 'max:255'],
            'gender'                  => ['nullable', 'string', 'in:male,female'],
            'age'                     => ['nullable', 'integer', 'min:18', 'max:100'],
            'dob'                     => ['nullable', 'date'],
            'phone_number'            => ['nullable', 'string', 'max:20'],
            'country_code'            => ['nullable', 'string', 'max:5'],
            'photo'                   => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],

            // Basic Info
            'base_salary'             => ['nullable', 'numeric', 'min:0'],
            'employment_type'         => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'shift_type'              => ['nullable', 'string'],
            'work_status'             => ['nullable', 'string'],
            'is_active'               => ['nullable', 'boolean'],
            'branch_ids'              => ['nullable', 'array'],
            'branch_ids.*'            => ['exists:branches,id'],
            
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

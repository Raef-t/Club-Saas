<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handle authorization via policies if needed
    }

    public function rules(): array
    {
        return [
            // Person Fields
            'first_name'              => ['required', 'string', 'max:255'],
            'last_name'               => ['required', 'string', 'max:255'],
            'gender'                  => ['nullable', 'string', 'in:male,female'],
            'age'                     => ['required', 'integer', 'min:18', 'max:100'],
            'dob'                     => ['nullable', 'date'],
            'phone_number'            => ['nullable', 'string', 'max:20'],
            'country_code'            => ['nullable', 'string', 'max:5'],
            'photo'                   => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            
            // Staff & Coach Details
            'branch_ids'              => ['required', 'array'],
            'branch_ids.*'            => ['exists:branches,id'],
            'employment_type'         => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'base_salary'             => ['nullable', 'numeric', 'min:0'],
            'specialization'          => ['nullable', 'string', 'max:255'],
            'bio'                     => ['nullable', 'string'],
            'experience_years'        => ['nullable', 'integer', 'min:0'],
            'payment_type'            => ['nullable', 'string'],
            'commission_type'         => ['nullable', 'string'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0'],
            'working_hours_per_week'  => ['nullable', 'numeric', 'min:0'],
            'gym_type'                => ['nullable', 'string'],
            'start_date'              => ['nullable', 'date'],
            'end_date'                => ['nullable', 'date', 'after_or_equal:start_date'],
            'contract_type'           => ['nullable', 'string'],
            'shift_type'              => ['nullable', 'string'],
            'work_type'               => ['nullable', 'string'],
            'work_status'             => ['nullable', 'string'],
            'is_active'               => ['nullable', 'boolean'],
        ];
    }
}

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

        // Filter empty values from arrays sent via multipart/form-data
        $arrayFields = ['branch_ids', 'work_types'];
        foreach ($arrayFields as $field) {
            if ($this->has($field) && is_array($this->input($field))) {
                $filtered = array_filter($this->input($field), fn($value) => !is_null($value) && $value !== '');
                $this->merge([$field => array_values($filtered)]);
            }
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
            'work_types'              => ['nullable', 'array'],
            'work_types.*'            => ['string', 'in:equipment,activities'],
            'work_status'             => ['nullable', 'string'],
            'is_active'               => ['nullable', 'boolean'],
            'branch_ids'              => ['nullable', 'array'],
            'branch_ids.*'            => ['exists:branches,id'],

            // Details Info
            'bio'                     => ['nullable', 'string'],
            'experience_years'        => ['nullable', 'integer', 'min:0'],
            'gym_type'                => ['nullable', 'string', 'in:male,female,mixed'],
            'payment_type'            => ['nullable', 'string'],
            'commission_type'         => ['nullable', 'string'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:200',
            'mobile_1' => 'required|string',
            'role' => 'required|in:admin,receptionist,coach,cleaner,manager',
            'employment_type' => 'required|in:fixed_salary,commission_based,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'required|exists:branches,id',
            'specialization' => 'nullable|string|max:100',
        ];
    }
}

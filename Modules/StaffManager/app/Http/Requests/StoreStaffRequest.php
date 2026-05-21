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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_type' => 'nullable|in:probation,permanent',
            'work_type' => 'nullable|in:part_time,full_time',
            'work_status' => 'nullable|in:active,suspended,on_leave',
            'salary_type' => 'nullable|in:monthly,commission,weekly',
            'employee_type' => 'nullable|in:receptionist,equipment_coach,cleaner,accountant,manager,supervisor,nursery',
            'other_tasks' => 'nullable|string|max:500',
            'gym_type' => 'nullable|in:male,female,mixed',
            'email' => 'nullable|email',
        ];
    }
}

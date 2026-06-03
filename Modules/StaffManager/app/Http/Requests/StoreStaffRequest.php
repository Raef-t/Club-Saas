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
            // Personal Details (people table)
            'full_name' => 'required|string|max:200',
            'mobile_1' => 'required|string',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date',
            'email' => 'nullable|email',
            'national_id' => 'nullable|string|max:50',
            'social_status' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'photo_url' => 'nullable|string|max:255',
            'mobile_2' => 'nullable|string|max:20',
            'landline' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'chronic_diseases' => 'nullable|string',
            'children_count' => 'nullable|integer|min:0',
            'how_did_you_hear' => 'nullable|string|max:100',
            'notes' => 'nullable|string',

            // Staff/Trainer Details (staff table)
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
            'shift_type' => 'nullable|string|max:50',
            'certificates_held' => 'nullable|array',
            'certificates_held.*' => 'string',

            // User login account info
            'username' => 'nullable|string|max:100|unique:authentication_users,username',
            'password' => 'nullable|string|min:6',
        ];
    }
}

<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Personal Details ─────────────────────────────────
            'full_name' => 'nullable|string|max:200',
            'mobile_1_country_code' => 'nullable|string|max:5',
            'mobile_1' => 'nullable|string',
            'email' => 'nullable|email',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date',
            'national_id' => 'nullable|string|max:50',
            'social_status' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'photo_url' => 'nullable|string|max:255',
            'mobile_2_country_code' => 'nullable|string|max:5',
            'mobile_2' => 'nullable|string|max:20',
            'landline' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_country_code' => 'nullable|string|max:5',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'chronic_diseases' => 'nullable|string',
            'children_count' => 'nullable|integer|min:0',
            'how_did_you_hear' => 'nullable|string|max:100',
            'notes' => 'nullable|string',

            // ── Staff Details ────────────────────────────────────
            'role' => 'nullable|in:admin,receptionist,coach,cleaner,manager,staff',
            'employment_type' => 'nullable|in:fixed_salary,commission_based,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_type' => 'nullable|in:probation,permanent',
            'shift_type' => 'nullable|string|max:50',
            'work_type' => 'nullable|in:part_time,full_time',
            'work_status' => 'nullable|in:active,suspended,on_leave',
            'other_tasks' => 'nullable|string|max:500',

            // ── Coach Details (only applies when staff is a coach) ──
            'specialization' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0',
            'payment_type' => 'nullable|string|max:50',
            'commission_type' => 'nullable|string|max:50',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
            'working_hours_per_week' => 'nullable|numeric|min:0',
            'gym_type' => 'nullable|in:male,female,mixed',
        ];
    }
}

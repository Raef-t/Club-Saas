<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
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
            // ── Personal Details (people table) ──────────────────
            'full_name' => 'required|string|max:200',
            'country_code' => 'nullable|string|max:5',
            'phone_number' => 'required|string',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date',
            'email' => 'nullable|email',
            'national_id' => 'nullable|string|max:50',
            'social_status' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'photo_url' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'secondary_country_code' => 'nullable|string|max:5',
            'secondary_phone_number' => 'nullable|string|max:20',
            'landline' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_country_code' => 'nullable|string|max:5',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'chronic_diseases' => 'nullable|string',
            'children_count' => 'nullable|integer|min:0',
            'how_did_you_hear' => 'nullable|string|max:100',
            'notes' => 'nullable|string',

            // ── Staff Details (staff table) ──────────────────────
            'role' => 'required|in:admin,receptionist,coach,cleaner,manager,staff',
            'employment_type' => 'required|in:fixed_salary,commission_based,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'branch_ids' => 'required|array',
            'branch_ids.*' => 'exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_type' => 'nullable|in:probation,permanent',
            'shift_type' => 'nullable|string|max:50',
            'work_type' => 'nullable|in:part_time,full_time',
            'work_status' => 'nullable|in:active,suspended,on_leave',
            'other_tasks' => 'nullable|string|max:500',

            // ── Coach Details (coach_details table, only when role=coach) ──
            'specialization' => 'nullable|required_if:role,coach|string|max:100',
            'bio' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0',
            'payment_type' => 'nullable|string|max:50',
            'commission_type' => 'nullable|string|max:50',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
            'working_hours_per_week' => 'nullable|numeric|min:0',
            'gym_type' => 'nullable|in:male,female,mixed',

            // ── Certifications (coach_certifications table) ──────
            'certifications' => 'nullable|array',
            'certifications.*.name' => 'required_with:certifications|string|max:200',
            'certifications.*.issuer' => 'nullable|string|max:200',
            'certifications.*.issue_date' => 'nullable|date',
            'certifications.*.expiry_date' => 'nullable|date|after_or_equal:certifications.*.issue_date',
            'certifications.*.document_url' => 'nullable|string|max:255',

            // ── User login account info ──────────────────────────
            'username' => 'nullable|string|max:100|unique:authentication_users,username',
            'password' => 'nullable|string|min:6',
        ];
    }
}

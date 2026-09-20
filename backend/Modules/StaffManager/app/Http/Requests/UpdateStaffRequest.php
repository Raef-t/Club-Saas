<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('first_name') || $this->has('last_name')) {
            $firstName = $this->first_name ?? '';
            $lastName = $this->last_name ?? '';
            $this->merge([
                'full_name' => trim($firstName . ' ' . $lastName)
            ]);
        }

        if ($this->has('branch_id') && !$this->has('branch_ids')) {
            $this->merge([
                'branch_ids' => is_array($this->branch_id) ? $this->branch_id : [$this->branch_id]
            ]);
        }

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
            'reason' => 'required|string|max:500',

            // ── Personal Details (people table) ──────────────────
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'full_name' => 'nullable|string|max:200',
            'country_code' => 'nullable|string|max:5',
            'phone_number' => 'required|string',
            'gender' => 'nullable|in:male,female',
            'dob' => 'nullable|date',
            'national_id' => 'nullable|string|max:50',
            'social_status' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'photo_url' => 'nullable|string|max:255',
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
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('is_visible', true);
                }),
            ],
            'employment_type' => 'required|in:fixed_salary,commission_based,hybrid',
            'base_salary' => 'nullable|numeric|min:0',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'work_type' => 'nullable|in:part_time,full_time',
            'work_status' => 'nullable|in:active,suspended,on_leave',

            // ── Coach Details (coach_details table, only when role=coach) ──
            'specialization' => 'nullable|required_if:role,coach|string|max:100',
            'bio' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0',
            'payment_type' => 'nullable|string|max:50',
            'commission_type' => 'nullable|string|max:50',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
            'private_commission_rate' => 'nullable|numeric|min:0|max:100',
            'working_hours_per_week' => 'nullable|numeric|min:0',
            'gym_type' => 'nullable|in:male,female,mixed',

            // ── Certifications (coach_certifications table) ──────
            'certifications' => 'nullable|array',
            'certifications.*.name' => 'required_with:certifications|string|max:200',
            'certifications.*.issuer' => 'nullable|string|max:200',
            'certifications.*.issue_date' => 'nullable|date',
            'certifications.*.expiry_date' => 'nullable|date|after_or_equal:certifications.*.issue_date',
            'certifications.*.document_url' => 'nullable|string|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $staffId = $this->route('id') ?? $this->route('staff');
            $staff = \Modules\StaffManager\Models\Staff::with(['person', 'branches'])->find($staffId);

            $gender = $this->has('gender') ? $this->input('gender') : $staff?->person?->gender;

            if (!$gender) {
                return;
            }

            $branchIds = $this->has('branch_ids')
                ? $this->input('branch_ids', [])
                : ($staff ? $staff->branches->pluck('id')->toArray() : []);

            if (!empty($branchIds) && is_array($branchIds)) {
                $branches = \Modules\ClubManager\Models\Branch::whereIn('id', $branchIds)->get();
                foreach ($branches as $branch) {
                    if ($branch->gender_restriction && $branch->gender_restriction !== 'mixed' && $branch->gender_restriction !== $gender) {
                        $branchName = $branch->name;
                        if (is_string($branchName)) {
                            $decoded = json_decode($branchName, true);
                            if (is_array($decoded)) {
                                $branchName = $decoded['ar'] ?? ($decoded['en'] ?? reset($decoded));
                            }
                        } elseif (is_array($branchName)) {
                            $branchName = $branchName['ar'] ?? ($branchName['en'] ?? reset($branchName));
                        }

                        $msg = $branch->gender_restriction === 'female'
                            ? "الفرع ({$branchName}) مخصص للإناث فقط، لا يمكن تعيين موظف ذكر."
                            : "الفرع ({$branchName}) مخصص للذكور فقط، لا يمكن تعيين موظفة أنثى.";
                        $validator->errors()->add('gender', $msg);
                        break;
                    }
                }
            }
        });
    }
}

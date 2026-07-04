<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachBasicInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_salary'     => ['nullable', 'numeric', 'min:0'],
            'employment_type' => ['nullable', 'string', 'in:fixed_salary,commission_based,hybrid'],
            'shift_type'      => ['nullable', 'string'],
            'work_status'     => ['nullable', 'string'],
            'is_active'       => ['nullable', 'boolean'],
            'branch_ids'      => ['nullable', 'array'],
            'branch_ids.*'    => ['exists:branches,id'],
        ];
    }
}

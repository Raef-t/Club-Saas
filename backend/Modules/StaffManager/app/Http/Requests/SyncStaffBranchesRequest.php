<?php

namespace Modules\StaffManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncStaffBranchesRequest extends FormRequest
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
            'branch_ids' => 'required|array',
            'branch_ids.*' => 'integer',
        ];
    }
}

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
            'branch_ids' => 'required|array',
            'branch_ids.*' => 'integer|exists:branches,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $staffId = $this->route('id') ?? $this->route('staff');
            $staff = \Modules\StaffManager\Models\Staff::with('person')->find($staffId);
            $gender = $staff?->person?->gender;

            if ($gender && $this->has('branch_ids') && is_array($this->branch_ids)) {
                $branches = \Modules\ClubManager\Models\Branch::whereIn('id', $this->branch_ids)->get();
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
                        $validator->errors()->add('branch_ids', $msg);
                        break;
                    }
                }
            }
        });
    }
}

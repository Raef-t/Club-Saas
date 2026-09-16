<?php

namespace Modules\AttendanceManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceptionCheckInAndDeductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // 1. Normalize member_id and attendable_id
        $memberId = $this->input('member_id') ?? $this->input('attendable_id');
        if ($memberId !== null) {
            $mergeData['attendable_id'] = (int) $memberId;
            $mergeData['member_id'] = (int) $memberId;
        }

        if (!$this->has('attendable_type')) {
            $mergeData['attendable_type'] = 'member';
        }

        // 2. Normalize subscription IDs
        $rawSubIds = $this->input('player_subscription_ids')
            ?? $this->input('subscription_ids')
            ?? $this->input('player_subscription_id')
            ?? $this->input('subscription_id');

        if (!is_null($rawSubIds)) {
            if (!is_array($rawSubIds)) {
                $rawSubIds = [$rawSubIds];
            }
            $mergeData['player_subscription_ids'] = array_map('intval', $rawSubIds);
        }

        // 3. Normalize notes / override reason
        $reason = $this->input('notes') ?? $this->input('reason') ?? $this->input('override_reason');
        if ($reason !== null) {
            $mergeData['notes'] = $reason;
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    public function rules(): array
    {
        return [
            'member_id'                 => ['required_without:attendable_id', 'integer'],
            'attendable_id'             => ['required_without:member_id', 'integer'],
            'attendable_type'           => ['nullable', 'string', 'in:member'],
            'branch_id'                 => ['required', 'integer', 'exists:branches,id'],
            'facility_id'               => ['nullable', 'integer'],
            'locker_id'                 => ['nullable', 'integer', 'exists:lockers,id'],
            'check_in_at'               => ['nullable', 'date'],
            'player_subscription_ids'   => ['nullable', 'array'],
            'player_subscription_ids.*' => ['integer'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'reason'                    => ['nullable', 'string', 'max:1000'],
            'override_reason'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required_without'     => __('يرجى تحديد معرف العضو (member_id).'),
            'attendable_id.required_without' => __('يرجى تحديد معرف العضو (attendable_id).'),
            'branch_id.required'             => __('يرجى تحديد الفرع (branch_id).'),
            'branch_id.exists'               => __('الفرع المحدد غير موجود.'),
            'locker_id.exists'               => __('الخزانة المحددة غير موجودة.'),
        ];
    }
}

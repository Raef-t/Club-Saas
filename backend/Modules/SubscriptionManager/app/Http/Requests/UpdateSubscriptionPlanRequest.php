<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $merge = [];
        if ($this->has('is_unlimited_subscribers') && filter_var($this->is_unlimited_subscribers, FILTER_VALIDATE_BOOLEAN)) {
            $merge['max_subscribers'] = 0;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'nullable|string|max:150',
            'session_count' => 'nullable|integer|min:1',
            'sessions_per_week' => 'nullable|integer|min:1',
            'base_price' => 'nullable|numeric|min:0',
            'max_subscribers' => 'nullable|integer|min:0',
            'is_unlimited_subscribers' => 'nullable|boolean',
            'gender_restriction' => 'nullable|in:male,female,mixed',
            'club_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'coach_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'completed'])],
            'activities' => 'nullable|array',
            'activities.*.activity_id' => 'required_with:activities|exists:activities,id',
            'activities.*.coach_id' => 'nullable|exists:staff,id',
            'session_templates' => 'nullable|array',
            'session_templates.*.facility_id' => 'nullable|exists:facilities,id',
            'session_templates.*.day_of_week' => 'required_with:session_templates|integer|between:0,6',
            'session_templates.*.start_time' => 'required_with:session_templates|date_format:H:i',
            'session_templates.*.end_time' => 'required_with:session_templates|date_format:H:i|after:session_templates.*.start_time',
        ];
    }
}

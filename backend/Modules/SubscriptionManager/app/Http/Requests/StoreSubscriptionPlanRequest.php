<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
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

        if (empty($this->duration_days) && $this->start_date && $this->end_date) {
            $start = \Carbon\Carbon::parse($this->start_date);
            $end = \Carbon\Carbon::parse($this->end_date);
            $merge['duration_days'] = $start->diffInDays($end);
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:150',
            'type' => 'required|in:fixed_period,session_based',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration_days' => 'required_if:type,fixed_period|nullable|integer|min:1',
            'session_count' => 'required_if:type,session_based|nullable|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'max_subscribers' => 'nullable|integer|min:0',
            'is_unlimited_subscribers' => 'nullable|boolean',
            'is_active' => 'boolean',
            'activities' => 'nullable|array',
            'activities.*.staff_activity_id' => 'required_with:activities|exists:staff_activities,id',
            'session_templates' => 'nullable|array',
            'session_templates.*.facility_id' => 'nullable|exists:facilities,id',
            'session_templates.*.day_of_week' => 'required_with:session_templates|integer|between:0,6',
            'session_templates.*.start_time' => 'required_with:session_templates|date_format:H:i',
            'session_templates.*.end_time' => 'required_with:session_templates|date_format:H:i|after:session_templates.*.start_time',
            'session_templates.*.gender_allowed' => 'required_with:session_templates|in:male,female,both',
        ];
    }
}

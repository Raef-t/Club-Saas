<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if (!$this->filled('branch_id') && $this->filled('plans') && is_array($this->plans)) {
            $firstPlan = \Modules\SubscriptionManager\Models\SubscriptionPlan::find($this->plans[0] ?? null);
            if ($firstPlan && $firstPlan->branch_id) {
                $this->merge(['branch_id' => $firstPlan->branch_id]);
            }
        }
    }

    public function rules()
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'offer_type' => 'nullable|string|in:bundle,single_choice',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
            'plans' => 'required|array|min:1',
            'plans.*' => 'exists:subscription_plans,id',
        ];
    }
}

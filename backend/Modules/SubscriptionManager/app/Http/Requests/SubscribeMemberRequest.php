<?php

namespace Modules\SubscriptionManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Modules\SubscriptionManager\Domain\Rules\NoActiveSubscriptionRule;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;

class SubscribeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subscriptionRepo = app(PlayerSubscriptionRepositoryInterface::class);

        return [
            'member_id' => [
                'required',
                'exists:members,id',
                new NoActiveSubscriptionRule($subscriptionRepo, $this->member_id, $this->plan_id)
            ],
            'plan_id' => 'required|exists:subscription_plans,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}

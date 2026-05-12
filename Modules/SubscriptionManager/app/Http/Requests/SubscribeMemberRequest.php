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
                new NoActiveSubscriptionRule($subscriptionRepo, $this->member_id)
            ],
            'plan_id' => 'required|exists:subscription_plans,id',
            'start_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
        ];
    }
}

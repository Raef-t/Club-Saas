<?php

namespace Modules\SubscriptionManager\Domain\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;

class NoActiveSubscriptionRule implements ValidationRule
{
    protected $repository;
    protected $memberId;
    protected $planId;

    public function __construct(PlayerSubscriptionRepositoryInterface $repository, $memberId, $planId = null)
    {
        $this->repository = $repository;
        $this->memberId = $memberId;
        $this->planId = $planId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->memberId || !$this->planId) {
            return;
        }

        $activeSubscription = $this->repository->findActiveByMemberAndPlan($this->memberId, $this->planId);

        if ($activeSubscription) {
            $fail(__('The member already has an active subscription to this plan.'));
        }
    }
}

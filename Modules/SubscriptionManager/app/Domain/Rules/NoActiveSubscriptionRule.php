<?php

namespace Modules\SubscriptionManager\Domain\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;

class NoActiveSubscriptionRule implements ValidationRule
{
    protected $repository;
    protected $memberId;

    public function __construct(PlayerSubscriptionRepositoryInterface $repository, $memberId)
    {
        $this->repository = $repository;
        $this->memberId = $memberId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->memberId) {
            return;
        }

        $activeSubscription = $this->repository->findActiveByMember($this->memberId);

        if ($activeSubscription) {
            $fail(__('The member already has an active subscription.'));
        }
    }
}

<?php

namespace Modules\SubscriptionManager\Domain\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;

class NoActiveSubscriptionRule implements ValidationRule
{
    protected $repository;
    protected $memberId;
    protected $planId;
    protected $requestedStartDate;

    public function __construct(PlayerSubscriptionRepositoryInterface $repository, $memberId, $planId = null, $requestedStartDate = null)
    {
        $this->repository = $repository;
        $this->memberId = $memberId;
        $this->planId = $planId;
        $this->requestedStartDate = $requestedStartDate;
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
            // If no start date provided, keep original behavior
            if (!$this->requestedStartDate) {
                $fail('العضو لديه اشتراك نشط بالفعل لهذا الخطة.');
                return;
            }
            $existingEnd = $activeSubscription->end_date ? Carbon::parse($activeSubscription->end_date) : null;
            $newStart = Carbon::parse($this->requestedStartDate);
            // Disallow if existing ends on or after new start
            if ($existingEnd && $existingEnd->greaterThanOrEqualTo($newStart)) {
                $fail('العضو لديه اشتراك نشط يتداخل مع تاريخ البدء المطلوب.');
            }
        }
    }
}

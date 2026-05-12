<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Repositories\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SubscriptionService
{
    protected $planRepository;
    protected $subscriptionRepository;

    public function __construct(
        SubscriptionPlanRepositoryInterface $planRepository,
        PlayerSubscriptionRepositoryInterface $subscriptionRepository
    ) {
        $this->planRepository = $planRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Subscribe a member to a plan.
     */
    public function subscribeMember($memberId, $planId, array $options = [])
    {
        // 1. Load plan with activities and ensure it exists
        $plan = $this->planRepository->find($planId);
        $plan->load('activities');
        
        return DB::transaction(function () use ($memberId, $plan, $options) {
            // 2. Dates Calculation
            $startDate = isset($options['start_date']) ? Carbon::parse($options['start_date']) : now();
            $endDate = null;

            if ($plan->type === 'fixed_period' && $plan->duration_days) {
                $endDate = $startDate->copy()->addDays($plan->duration_days);
            }

            // 3. Financials
            $totalAmount = $plan->base_price;
            $paidAmount = $options['paid_amount'] ?? $totalAmount;
            $remainingAmount = max(0, $totalAmount - $paidAmount);

            // 4. Create Subscription
            $subscription = $this->subscriptionRepository->create([
                'tenant_id' => Auth::check() ? Auth::user()->tenant_id : ($options['tenant_id'] ?? 1),
                'member_id' => $memberId,
                'coach_id' => $options['coach_id'] ?? null,
                'plan_id' => $plan->id,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate ? $endDate->toDateString() : null,
                'remaining_sessions' => $plan->session_count,
                'status' => 'active',
                'notes' => $options['notes'] ?? null,
            ]);

            // 5. Create Subscription Items
            foreach ($plan->activities as $activity) {
                $subscription->items()->create([
                    'tenant_id' => $subscription->tenant_id,
                    'activity_id' => $activity->id,
                    'sessions_allocated' => $activity->pivot->sessions_count,
                    'is_unlimited' => $activity->pivot->is_unlimited,
                ]);
            }

            return $subscription;
        });
    }

    /**
     * Freeze a subscription.
     */
    public function freezeSubscription($subscriptionId, $startDate, $endDate, $reason = null)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        return DB::transaction(function () use ($subscription, $startDate, $endDate, $reason) {
            $subscription->freezes()->create([
                'tenant_id' => $subscription->tenant_id,
                'freeze_start_date' => $startDate,
                'freeze_end_date' => $endDate,
                'reason' => $reason,
            ]);

            $subscription->update(['status' => 'frozen']);

            return $subscription;
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription($subscriptionId, array $options = [])
    {
        $oldSubscription = $this->subscriptionRepository->find($subscriptionId);
        
        // Ensure plan is loaded
        $plan = $oldSubscription->plan;

        return DB::transaction(function () use ($oldSubscription, $plan, $options) {
            // New start date is either after the old one ends or NOW if it already ended
            $startDate = $oldSubscription->end_date && Carbon::parse($oldSubscription->end_date)->isFuture() 
                ? Carbon::parse($oldSubscription->end_date) 
                : now();

            $options['coach_id'] = $options['coach_id'] ?? $oldSubscription->coach_id;
            $options['start_date'] = $startDate->toDateString();

            return $this->subscribeMember($oldSubscription->member_id, $plan->id, $options);
        });
    }

    /**
     * Record a payment for a subscription.
     */
    public function recordPayment($subscriptionId, $amount)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);
        
        $newPaidAmount = $subscription->paid_amount + $amount;
        
        // Ensure we don't pay more than total
        if ($newPaidAmount > $subscription->total_amount) {
            $newPaidAmount = $subscription->total_amount;
        }

        $newRemainingAmount = max(0, $subscription->total_amount - $newPaidAmount);

        $subscription->update([
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $newRemainingAmount
        ]);

        return $subscription;
    }
}

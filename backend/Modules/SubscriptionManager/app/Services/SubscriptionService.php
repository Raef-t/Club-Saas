<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Repositories\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionManager\Repositories\PlayerSubscriptionRepositoryInterface;
use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\Core\Contracts\MemberSharedServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class SubscriptionService
{
    protected SubscriptionPlanRepositoryInterface $planRepository;
    protected PlayerSubscriptionRepositoryInterface $subscriptionRepository;
    protected MemberSharedServiceInterface $memberSharedService;

    public function __construct(
        SubscriptionPlanRepositoryInterface $planRepository,
        PlayerSubscriptionRepositoryInterface $subscriptionRepository,
        MemberSharedServiceInterface $memberSharedService
    ) {
        $this->planRepository = $planRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->memberSharedService = $memberSharedService;
    }

    /**
     * Get all player subscriptions with resolved Member DTOs.
     */
    public function getAllSubscriptions(array $filters = [])
    {
        $query = PlayerSubscription::query()->with(['plan', 'items.activity', 'items.coach.person']);

        if (!empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (!empty($filters['plan_id'])) {
            $query->where('plan_id', $filters['plan_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->whereHas('plan', function ($q) use ($filters) {
                $q->where('branch_id', $filters['branch_id']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['coach_id'])) {
            $query->where('coach_id', $filters['coach_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        $subscriptions = $query->latest()->get();

        foreach ($subscriptions as $subscription) {
            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);
        }

        return $subscriptions;
    }

    /**
     * Get a single subscription with resolved Member DTO.
     */
    public function getSubscriptionById(int $id)
    {
        $subscription = $this->subscriptionRepository->find($id);
        if ($subscription) {
            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);
        }
        return $subscription;
    }

    /**
     * Subscribe a member to a plan.
     */
    public function subscribeMember(int $memberId, int $planId, array $options = [])
    {
        // 1. Load plan with activities and ensure it exists
        $plan = $this->planRepository->find($planId);
        $plan->load('planActivities');

        if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
            throw new Exception(__('This subscription plan has reached its maximum capacity.'));
        }

        return DB::transaction(function () use ($memberId, $plan, $options) {
            if ($plan->max_subscribers > 0) {
                $plan->increment('current_subscribers');
                
                // Automatically deactivate the plan if it's now full
                if ($plan->current_subscribers >= $plan->max_subscribers) {
                    $plan->update(['is_active' => false]);
                }
            }


            // 2. Dates Calculation
            $startDate = isset($options['start_date']) ? Carbon::parse($options['start_date']) : now();
            $endDate = null;

            if ($plan->type === 'fixed_period' && $plan->duration_days) {
                $endDate = $startDate->copy()->addDays($plan->duration_days);
            }

            // 3. Financials
            $totalAmount = $plan->base_price;
            $paidAmount = $options['paid_amount'] ?? $totalAmount;

            if ($paidAmount < $totalAmount) {
                $memberDTO = $this->memberSharedService->getMemberById($memberId);
                $branchId = $memberDTO->branchId;
                if (!$branchId) {
                    throw new Exception(__('Member does not belong to any branch.'));
                }
                $branch = \Modules\ClubManager\Models\Branch::find($branchId);
                if (!$branch) {
                    throw new Exception(__('Branch not found.'));
                }
                $clubId = $branch->club_id;

                $clubSetting = \Modules\ClubManager\Models\ClubSetting::where('club_id', $clubId)->first();
                if ($clubSetting && !$clubSetting->allow_partial_payment) {
                    throw new Exception(__('Partial payments are not allowed for this club. Please pay the full amount.'));
                }
            }

            $remainingAmount = max(0, $totalAmount - $paidAmount);

            // 4. Create Subscription
            $subscription = $this->subscriptionRepository->create([
                'member_id' => $memberId,
                'coach_id' => $options['coach_id'] ?? null,
                'plan_id' => $plan->id,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate ? $endDate->toDateString() : null,
                'status' => 'active',
                'notes' => $options['notes'] ?? null,
            ]);

            // 5. Create Subscription Items
            $requestedActivities = collect($options['activities'] ?? []);

            foreach ($plan->planActivities as $planActivity) {
                $activityId = $planActivity->activity_id;
                $coachId = $planActivity->coach_id;

                $subscription->items()->create([
                    'activity_id' => $activityId,
                    'coach_id' => $coachId,
                    'sessions_allocated' => $plan->session_count,
                    'is_unlimited' => is_null($plan->session_count),
                ]);
            }

            // 6. Create Invoice
            $memberDTO = $this->memberSharedService->getMemberById($memberId);
            $branchId = $memberDTO->branchId;
            if (!$branchId) {
                throw new Exception(__('Member does not belong to any branch.'));
            }

            $invoice = \Modules\SubscriptionManager\Models\Invoice::create([
                'member_id' => $memberId,
                'branch_id' => $branchId,
                'player_subscription_id' => $subscription->id,
                'total' => $totalAmount,
                'status' => $remainingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid'),
            ]);

            // 7. Create Payment if paid_amount > 0
            if ($paidAmount > 0) {
                if (($options['payment_method'] ?? 'cash') === 'wallet') {
                    // Pay via wallet
                    $walletService = app(\Modules\WalletManager\Services\WalletService::class);
                    $walletService->pay(
                        $memberDTO->personId,
                        $paidAmount,
                        'Subscription Payment for Invoice #' . $invoice->id,
                        \Modules\SubscriptionManager\Models\Invoice::class,
                        $invoice->id
                    );

                    $payment = \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'safe_id' => null, // No safe involved since money is taken from wallet
                        'amount' => $paidAmount,
                        'payment_method' => 'wallet',
                        'status' => 'completed',
                    ]);
                } else {
                    $safeId = \Illuminate\Support\Facades\DB::table('acc_branch_settings')
                        ->where('branch_id', $branchId)
                        ->value('default_safe_id');

                    if (!$safeId) {
                        $safeId = \Illuminate\Support\Facades\DB::table('acc_safes')
                            ->where('branch_id', $branchId)
                            ->value('id');
                    }

                    $payment = \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'safe_id' => $safeId,
                        'amount' => $paidAmount,
                        'payment_method' => $options['payment_method'] ?? 'cash',
                        'status' => 'completed',
                    ]);
                }

                event(new \Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded($payment));
            }

            // 8. Create Extra Services (including Lockers)
            if (!empty($options['extra_services']) && is_array($options['extra_services'])) {
                foreach ($options['extra_services'] as $serviceData) {
                    $priceCharged = $serviceData['price_charged'] ?? 0;
                    $lockerId = $serviceData['locker_id'] ?? null;

                    if ($lockerId) {
                        $locker = \Illuminate\Support\Facades\DB::table('lockers')->where('id', $lockerId)->first();
                        if (!$locker) {
                            throw new Exception(__('Selected locker not found.'));
                        }
                        if ($locker->status !== 'available') {
                            throw new Exception(__('Locker :number is already rented.', ['number' => $locker->locker_number]));
                        }

                        \Illuminate\Support\Facades\DB::table('lockers')->where('id', $lockerId)->update([
                            'status' => 'rented',
                            'updated_at' => now(),
                        ]);
                    }

                    $subscription->services()->create([
                        'extra_service_id' => $serviceData['extra_service_id'],
                        'price_charged' => $priceCharged,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate ? $endDate->toDateString() : null,
                        'locker_id' => $lockerId,
                    ]);
                }
            }

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            return $subscription;
        });
    }

    /**
     * Freeze a subscription.
     */
    public function freezeSubscription(int $subscriptionId, string $startDate, string $endDate, ?string $reason = null)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        return DB::transaction(function () use ($subscription, $startDate, $endDate, $reason) {
            $plan = $subscription->plan;

            if ($plan) {
                // Validate max freeze count
                if ($plan->max_freeze_count !== null) {
                    $freezeCount = $subscription->freezes()->count();
                    if ($freezeCount >= $plan->max_freeze_count) {
                        throw new Exception(__('Freezing limit exceeded. This subscription plan allows at most :count freeze(s).', ['count' => $plan->max_freeze_count]));
                    }
                }

                // Validate max freeze days
                if ($plan->max_freeze_days !== null) {
                    $requestedDays = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

                    $previousFreezeDays = 0;
                    foreach ($subscription->freezes as $freeze) {
                        $end = $freeze->actual_end_date ?? $freeze->freeze_end_date;
                        $previousFreezeDays += Carbon::parse($freeze->freeze_start_date)->diffInDays(Carbon::parse($end)) + 1;
                    }

                    if ($previousFreezeDays + $requestedDays > $plan->max_freeze_days) {
                        throw new Exception(__('Total freezing days limit exceeded. This subscription plan allows at most :days days of freezing.', ['days' => $plan->max_freeze_days]));
                    }
                }
            }

            $subscription->freezes()->create([
                'freeze_start_date' => $startDate,
                'freeze_end_date' => $endDate,
                'reason' => $reason,
            ]);

            $subscription->update(['status' => 'frozen']);

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            return $subscription;
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(int $subscriptionId, array $options = [])
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
    public function recordPayment(int $subscriptionId, float $amount)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        return DB::transaction(function () use ($subscription, $amount) {
            $newPaidAmount = $subscription->paid_amount + $amount;

            if ($newPaidAmount > $subscription->total_amount) {
                $amount = max(0, $subscription->total_amount - $subscription->paid_amount);
                $newPaidAmount = $subscription->total_amount;
            }

            $newRemainingAmount = max(0, $subscription->total_amount - $newPaidAmount);

            $subscription->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => $newRemainingAmount
            ]);

            $memberDTO = $this->memberSharedService->getMemberById($subscription->member_id);
            $branchId = $memberDTO->branchId;
            if (!$branchId) {
                throw new Exception(__('Member does not belong to any branch.'));
            }

            $invoice = \Modules\SubscriptionManager\Models\Invoice::firstOrCreate(
                ['player_subscription_id' => $subscription->id],
                [
                    'member_id' => $subscription->member_id,
                    'branch_id' => $branchId,
                    'total' => $subscription->total_amount,
                    'status' => 'unpaid',
                ]
            );

            if ($amount > 0) {
                if (($options['payment_method'] ?? 'cash') === 'wallet') {
                    // Pay via wallet
                    $memberDTO = $this->memberSharedService->getMemberById($subscription->member_id);
                    $walletService = app(\Modules\WalletManager\Services\WalletService::class);
                    $walletService->pay(
                        $memberDTO->personId,
                        $amount,
                        'Subscription Payment for Invoice #' . $invoice->id,
                        \Modules\SubscriptionManager\Models\Invoice::class,
                        $invoice->id
                    );

                    $payment = \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'safe_id' => null,
                        'amount' => $amount,
                        'payment_method' => 'wallet',
                        'status' => 'completed',
                    ]);
                } else {
                    $safeId = \Illuminate\Support\Facades\DB::table('acc_branch_settings')
                        ->where('branch_id', $branchId)
                        ->value('default_safe_id');

                    if (!$safeId) {
                        $safeId = \Illuminate\Support\Facades\DB::table('acc_safes')
                            ->where('branch_id', $branchId)
                            ->value('id');
                    }

                    $payment = \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'safe_id' => $safeId,
                        'amount' => $amount,
                        'payment_method' => 'cash',
                        'status' => 'completed',
                    ]);
                }

                event(new \Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded($payment));
            }

            $invoice->update([
                'status' => $newRemainingAmount <= 0 ? 'paid' : 'partially_paid',
            ]);

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            return $subscription;
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(int $subscriptionId, ?string $reason = null)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        if ($subscription->status === 'cancelled') {
            throw new Exception(__('Subscription is already cancelled.'));
        }

        return DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => 'cancelled',
                'notes' => $subscription->notes
                    ? $subscription->notes . "\n" . __('Cancellation reason: ') . $reason
                    : __('Cancellation reason: ') . $reason,
            ]);

            // Release any rented lockers tied to this subscription
            $rentedLockerIds = $subscription->services()
                ->whereNotNull('locker_id')
                ->pluck('locker_id')
                ->toArray();

            if (!empty($rentedLockerIds)) {
                \Illuminate\Support\Facades\DB::table('lockers')
                    ->whereIn('id', $rentedLockerIds)
                    ->update([
                        'status' => 'available',
                        'updated_at' => now(),
                    ]);
            }

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            return $subscription;
        });
    }

    /**
     * Unfreeze a frozen subscription and extend end_date by freeze duration.
     */
    public function unfreezeSubscription(int $subscriptionId)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        if ($subscription->status !== 'frozen') {
            throw new Exception(__('Subscription is not frozen.'));
        }

        return DB::transaction(function () use ($subscription) {
            // Find the active freeze
            $activeFreeze = $subscription->freezes()
                ->whereNull('actual_end_date')
                ->latest()
                ->first();

            if ($activeFreeze) {
                $freezeDays = Carbon::parse($activeFreeze->freeze_start_date)->diffInDays(now());
                $activeFreeze->update(['actual_end_date' => now()]);

                // Extend end_date by freeze duration
                if ($subscription->end_date) {
                    $subscription->update([
                        'end_date' => Carbon::parse($subscription->end_date)->addDays($freezeDays),
                    ]);
                }
            }

            $subscription->update(['status' => 'active']);

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            return $subscription;
        });
    }

    /**
     * Get subscriptions expiring within the given number of days.
     */
    public function getExpiringSoon(int $days = 7)
    {
        return PlayerSubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->get();
    }

    /**
     * Subscribe a member to an offer, enrolling them in all included plans.
     */
    public function subscribeMemberToOffer(int $memberId, int $offerId, array $options = [])
    {
        $offer = \Modules\SubscriptionManager\Models\Offer::with(['plans.planActivities'])->findOrFail($offerId);

        if (!$offer->is_active) {
            throw new Exception(__('This offer is no longer active.'));
        }

        // Check capacity for all plans
        foreach ($offer->plans as $plan) {
            if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                throw new Exception(__('The plan :plan within this offer has reached its maximum capacity. Offer cannot be purchased.', ['plan' => $plan->name]));
            }
        }

        return DB::transaction(function () use ($memberId, $offer, $options) {
            // Increment subscribers for all plans
            foreach ($offer->plans as $plan) {
                if ($plan->max_subscribers > 0) {
                    $plan->increment('current_subscribers');
                    if ($plan->current_subscribers >= $plan->max_subscribers) {
                        $plan->update(['is_active' => false]);
                    }
                }
            }

            $startDate = isset($options['start_date']) ? Carbon::parse($options['start_date']) : now();

            $totalAmount = (float) $offer->price;
            $paidAmount = isset($options['paid_amount']) ? (float) $options['paid_amount'] : $totalAmount;
            $remainingAmount = max(0, $totalAmount - $paidAmount);

            // Fetch member details for financials
            $memberDTO = $this->memberSharedService->getMemberById($memberId);
            $branchId = $memberDTO->branchId;
            if (!$branchId) {
                throw new Exception(__('Member does not belong to any branch.'));
            }

            // Create Invoice linked to Offer
            $invoice = \Modules\SubscriptionManager\Models\Invoice::create([
                'member_id' => $memberId,
                'branch_id' => $branchId,
                'offer_id' => $offer->id,
                'player_subscription_id' => null,
                'total' => $totalAmount,
                'status' => $remainingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid'),
            ]);

            $createdSubscriptions = collect();

            // Create individual PlayerSubscriptions for each plan in the offer
            foreach ($offer->plans as $plan) {
                $endDate = null;
                if ($plan->type === 'fixed_period' && $plan->duration_days) {
                    $endDate = $startDate->copy()->addDays($plan->duration_days);
                }

                $subscription = $this->subscriptionRepository->create([
                    'member_id' => $memberId,
                    'coach_id' => $options['coach_id'] ?? null,
                    'plan_id' => $plan->id,
                    'offer_id' => $offer->id,
                    'total_amount' => 0, // Zero because it's part of the offer
                    'paid_amount' => 0,
                    'remaining_amount' => 0,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate ? $endDate->toDateString() : null,
                    'status' => 'active',
                    'notes' => $options['notes'] ?? __('Subscribed via offer: :offer', ['offer' => $offer->name]),
                ]);

                foreach ($plan->planActivities as $planActivity) {
                    $subscription->items()->create([
                        'activity_id' => $planActivity->activity_id,
                        'coach_id' => $planActivity->coach_id,
                        'sessions_allocated' => $plan->session_count,
                        'is_unlimited' => is_null($plan->session_count),
                    ]);
                }

                $createdSubscriptions->push($subscription);
            }

            // Record Payment if paid_amount > 0
            if ($paidAmount > 0) {
                if (($options['payment_method'] ?? 'cash') === 'wallet') {
                    $walletService = app(\Modules\WalletManager\Services\WalletService::class);
                    $walletService->pay(
                        $memberDTO->personId,
                        $paidAmount,
                        'Offer Payment for Invoice #' . $invoice->id,
                        \Modules\SubscriptionManager\Models\Invoice::class,
                        $invoice->id
                    );

                    $payment = \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'safe_id' => null,
                        'amount' => $paidAmount,
                        'payment_method' => 'wallet',
                        'status' => 'completed',
                    ]);
                } else {
                    $safeId = \Illuminate\Support\Facades\DB::table('acc_branch_settings')
                        ->where('branch_id', $branchId)
                        ->value('default_safe_id');

                    if (!$safeId) {
                        $safeId = \Illuminate\Support\Facades\DB::table('acc_safes')
                            ->where('branch_id', $branchId)
                            ->value('id');
                    }

                    $payment = \Modules\SubscriptionManager\Models\Payment::create([
                        'invoice_id' => $invoice->id,
                        'safe_id' => $safeId,
                        'amount' => $paidAmount,
                        'payment_method' => $options['payment_method'] ?? 'cash',
                        'status' => 'completed',
                    ]);
                }

                event(new \Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded($payment));
            }

            return [
                'invoice' => $invoice,
                'subscriptions' => $createdSubscriptions
            ];
        });
    }
}

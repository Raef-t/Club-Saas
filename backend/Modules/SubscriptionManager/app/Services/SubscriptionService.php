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
        $query = PlayerSubscription::query()->with(['plan.planActivities.staffActivity.activity', 'plan.planActivities.staffActivity.staff.person', 'items']);

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

        // Note: coach_id filter removed — coach info is now derived from subscription_plan → plan_activities

        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        $subscriptions = $query->latest()->get();

        $memberIds = $subscriptions->pluck('member_id')->filter()->unique()->toArray();
        if (!empty($memberIds)) {
            $members = $this->memberSharedService->getMembersByIds($memberIds);
            $membersMap = collect($members)->keyBy('id');
            foreach ($subscriptions as $subscription) {
                $subscription->member = $membersMap->get($subscription->member_id);
            }
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
        return DB::transaction(function () use ($memberId, $planId, $options) {
            // 1. Load and lock plan row inside transaction to prevent capacity overflow
            $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::where('id', $planId)
                ->lockForUpdate()
                ->firstOrFail();
            $plan->load('planActivities');

            if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                throw new Exception(__('This subscription plan has reached its maximum capacity.'));
            }

            $this->incrementPlanSubscribers($plan);


            // 2. Financials & Dates Calculation
            $monthsCount = max(1, (int) ($options['months_count'] ?? 1));
            $startDate = Carbon::parse($options['start_date']);
            $endDate = isset($options['end_date']) && !empty($options['end_date']) ? Carbon::parse($options['end_date']) : null;
            if (!$endDate) {
                if (!empty($options['duration_days'])) {
                    $endDate = $startDate->copy()->addDays((int) $options['duration_days']);
                } else {
                    $endDate = $startDate->copy()->addMonths($monthsCount);
                }
            }
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
                'plan_id' => $plan->id,
                'months_count' => $monthsCount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate ? $endDate->toDateString() : null,
                'status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value,
                'notes' => $options['notes'] ?? null,
            ]);

            // 5. Create Subscription Items (one item per plan activity)
            // Activity & coach info is now derived from subscription_plan → plan_activities → staff_activity
            foreach ($plan->planActivities as $planActivity) {
                $sessionsAllocated = !is_null($plan->session_count)
                    ? ($plan->session_count * $monthsCount)
                    : null;

                $subscription->items()->create([
                    'sessions_allocated' => $sessionsAllocated,
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



            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            // Dispatch SubscriptionCreated event so other modules (like StaffManager) can react
            event(new \Modules\SubscriptionManager\Events\SubscriptionCreated($subscription, $plan));

            return $subscription;
        });
    }

    /**
     * Freeze a subscription.
     */
    public function freezeSubscription(int $subscriptionId, string $startDate, ?string $reason = null)
    {
        $subscription = $this->subscriptionRepository->find($subscriptionId);

        return DB::transaction(function () use ($subscription, $startDate, $reason) {
            $memberModel = \Modules\MemberManager\Models\Member::with('branch.settings')->find($subscription->member_id);
            $allowFreeze = $memberModel?->branch?->settings?->allow_freeze ?? false;

            if (!$allowFreeze) {
                throw new Exception(__('Freezing is not allowed in this branch.'));
            }

            $currentStatus = is_object($subscription->status) ? $subscription->status->value : $subscription->status;
            if ($currentStatus === \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FROZEN->value) {
                throw new Exception(__('Subscription is already frozen.'));
            }

            $subscription->freezes()->create([
                'freeze_start_date' => $startDate,
                'freeze_end_date' => null,
                'reason' => $reason,
            ]);

            $subscription->update(['status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FROZEN->value]);
            // $this->decrementPlanSubscribers($subscription->plan);

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            // إرسال إشعار التجميد
            $member = \Modules\MemberManager\Models\Member::with('person.user')->find($subscription->member_id);
            $userId = $member?->person?->user?->id;

            if ($userId) {
                $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'subscription_frozen')->first();
                if ($template) {
                    $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';
                    $planName = $subscription->plan ? $subscription->plan->name : 'الاشتراك';

                    $startCarbon = \Carbon\Carbon::parse($startDate);

                    $startDay = $startCarbon->locale('ar')->translatedFormat('l');

                    $body = $template->parseBody([
                        'اسم اللاعب' => $playerName,
                        'اسم الاشتراك' => $planName,
                        'تاريخ البداية' => $startDate,
                        'يوم البداية' => $startDay,
                    ]);

                    app(\Modules\NotificationManager\Services\NotificationService::class)->createNotification([
                        'title' => $template->subject ?? 'تم تجميد اشتراكك مؤقتاً ❄️',
                        'body' => $body,
                        'user_ids' => [$userId],
                        'sender_type' => 'system'
                    ]);
                }
            }

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

            // coach_id is no longer stored per item; it is derived from the plan's planActivities
            $options['start_date'] = $startDate->toDateString();

            return $this->subscribeMember($oldSubscription->member_id, $plan->id, $options);
        });
    }

    /**
     * Record a payment for a subscription.
     */
    public function recordPayment(int $subscriptionId, float $amount, array $options = [])
    {
        return DB::transaction(function () use ($subscriptionId, $amount, $options) {
            $subscription = \Modules\SubscriptionManager\Models\PlayerSubscription::where('id', $subscriptionId)
                ->lockForUpdate()
                ->firstOrFail();

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

        if ($subscription->status->value === \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::TERMINATED->value) {
            throw new Exception(__('Subscription is already cancelled.'));
        }

        return DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::TERMINATED->value,
                'notes' => $subscription->notes
                    ? $subscription->notes . "\n" . __('Cancellation reason: ') . $reason
                    : __('Cancellation reason: ') . $reason,
            ]);

            $this->decrementPlanSubscribers($subscription->plan);



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

        if ($subscription->status->value !== \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::FROZEN->value) {
            throw new Exception(__('Subscription is not frozen.'));
        }

        return DB::transaction(function () use ($subscription) {
            if ($subscription->plan_id) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::where('id', $subscription->plan_id)
                    ->lockForUpdate()
                    ->first();
                if ($plan && $plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                    throw new Exception(__('لا يمكن فك التجميد لأن الخطة ممتلئة بالكامل حالياً.'));
                }
            }

            // Find the active freeze
            $activeFreeze = $subscription->freezes()
                ->whereNull('actual_end_date')
                ->latest()
                ->first();

            if ($activeFreeze) {
                $freezeDays = Carbon::parse($activeFreeze->freeze_start_date)->diffInDays(now());
                $activeFreeze->update(['actual_end_date' => now()]);

                $newEndDate = $this->calculateUnfreezeEndDate($subscription, $freezeDays);
                if ($newEndDate) {
                    $subscription->update(['end_date' => $newEndDate]);
                }
            }

            $subscription->update(['status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value]);
            // $this->incrementPlanSubscribers($subscription->plan);

            $subscription->member = $this->memberSharedService->getMemberById($subscription->member_id);

            $this->sendUnfreezeNotification($subscription);

            return $subscription;
        });
    }

    /**
     * Calculate the new end date when unfreezing a subscription based on its plan type.
     */
    private function calculateUnfreezeEndDate(PlayerSubscription $subscription, int $freezeDays): ?string
    {
        $plan = $subscription->plan;

        if ($plan && !is_null($plan->session_count)) {
            return $this->calculateSessionBasedEndDate($subscription, $freezeDays);
        }

        return $subscription->end_date
            ? Carbon::parse($subscription->end_date)->addDays($freezeDays)->format('Y-m-d')
            : null;
    }

    /**
     * Calculate end date for session-based plans by mapping remaining sessions to scheduled session template days.
     */
    private function calculateSessionBasedEndDate(PlayerSubscription $subscription, int $fallbackFreezeDays): ?string
    {
        $subscription->loadMissing(['items', 'plan.sessionTemplates']);

        $remainingSessions = $subscription->items->sum(function ($item) {
            return max(0, ($item->sessions_allocated ?? 0) - ($item->sessions_consumed ?? 0));
        });

        $sessionTemplates = $subscription->plan?->sessionTemplates->where('is_active', true) ?? collect();
        $allowedDays = $sessionTemplates->pluck('day_of_week')->map(fn($d) => (int)$d)->unique()->all();

        if (empty($allowedDays) || $remainingSessions <= 0) {
            return $subscription->end_date
                ? Carbon::parse($subscription->end_date)->addDays($fallbackFreezeDays)->format('Y-m-d')
                : null;
        }

        // Fetch cancelled session dates from exceptions
        $templateIds = $sessionTemplates->pluck('id')->toArray();
        $cancelledDates = \Modules\Sports\Models\SessionException::whereIn('sport_session_template_id', $templateIds)
            ->where('status', 'cancelled')
            ->pluck('date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $currentDate = now()->startOfDay();
        $sessionsCounted = 0;
        $calculatedEndDate = $currentDate->copy();

        $maxSearchDays = 730; // Maximum 2 years search horizon to prevent infinite loops
        $daysSearched = 0;

        while ($sessionsCounted < $remainingSessions && $daysSearched < $maxSearchDays) {
            $formattedDate = $currentDate->format('Y-m-d');

            if (in_array($currentDate->dayOfWeek, $allowedDays, true) && !in_array($formattedDate, $cancelledDates, true)) {
                $sessionsCounted++;
                $calculatedEndDate = $currentDate->copy();
            }

            if ($sessionsCounted < $remainingSessions) {
                $currentDate->addDay();
            }

            $daysSearched++;
        }

        return $calculatedEndDate->format('Y-m-d');
    }

    /**
     * Send notification to member when subscription is unfrozen.
     */
    private function sendUnfreezeNotification(PlayerSubscription $subscription): void
    {
        $member = \Modules\MemberManager\Models\Member::with('person.user')->find($subscription->member_id);
        $userId = $member?->person?->user?->id;

        if (!$userId) {
            return;
        }

        $template = \Modules\NotificationManager\Models\NotificationTemplate::where('system_key', 'subscription_unfrozen')->first();
        if (!$template) {
            return;
        }

        $playerName = $member?->person?->full_name ?? 'لاعبنا العزيز';
        $planName = $subscription->plan ? $subscription->plan->name : 'الاشتراك';

        $endDate = now();
        $endDay = $endDate->locale('ar')->translatedFormat('l');

        $body = $template->parseBody([
            'اسم اللاعب' => $playerName,
            'اسم الاشتراك' => $planName,
            'تاريخ النهاية' => $endDate->toDateString(),
            'يوم النهاية' => $endDay,
        ]);

        app(\Modules\NotificationManager\Services\NotificationService::class)->createNotification([
            'title' => $template->subject ?? 'انتهاء فترة التجميد وتفعيل الاشتراك 🟢',
            'body' => $body,
            'user_ids' => [$userId],
            'sender_type' => 'system'
        ]);
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

        return DB::transaction(function () use ($memberId, $offer, $options) {
            // Lock and check capacity for all plans inside transaction
            foreach ($offer->plans as $planItem) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::where('id', $planItem->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($plan->max_subscribers > 0 && $plan->current_subscribers >= $plan->max_subscribers) {
                    throw new Exception(__('The plan :plan within this offer has reached its maximum capacity. Offer cannot be purchased.', ['plan' => $plan->name]));
                }

                $this->incrementPlanSubscribers($plan);
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

            $monthsCount = max(1, (int) ($options['months_count'] ?? 1));

            // Create individual PlayerSubscriptions for each plan in the offer
            foreach ($offer->plans as $plan) {
                $endDate = isset($options['end_date']) ? Carbon::parse($options['end_date']) : null;
                if (!$endDate && !empty($options['duration_days'])) {
                    $endDate = $startDate->copy()->addDays((int) $options['duration_days']);
                }

                $subscription = $this->subscriptionRepository->create([
                    'member_id' => $memberId,
                    'plan_id' => $plan->id,
                    'months_count' => $monthsCount,
                    'offer_id' => $offer->id,
                    'total_amount' => 0, // Zero because it's part of the offer
                    'paid_amount' => 0,
                    'remaining_amount' => 0,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate ? $endDate->toDateString() : null,
                    'status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value,
                    'notes' => $options['notes'] ?? __('Subscribed via offer: :offer', ['offer' => $offer->name]),
                ]);

                // One item per plan activity; activity & coach derived from plan_activities → staff_activity
                foreach ($plan->planActivities as $planActivity) {
                    $sessionsAllocated = !is_null($plan->session_count)
                        ? ($plan->session_count * $monthsCount)
                        : null;

                    $subscription->items()->create([
                        'sessions_allocated' => $sessionsAllocated,
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

    /**
     * Increment plan subscribers and mark as completed if full.
     */
    public function incrementPlanSubscribers(?\Modules\SubscriptionManager\Models\SubscriptionPlan $plan)
    {
        if ($plan && $plan->max_subscribers > 0) {
            $plan->increment('current_subscribers');
            if ($plan->current_subscribers >= $plan->max_subscribers) {
                $plan->update(['status' => \Modules\SubscriptionManager\Enums\SubscriptionPlanStatus::COMPLETED->value]);
            }
        }
    }

    /**
     * Decrement plan subscribers and mark as active if space opens up from completed state.
     */
    public function decrementPlanSubscribers(?\Modules\SubscriptionManager\Models\SubscriptionPlan $plan)
    {
        if ($plan && $plan->max_subscribers > 0) {
            if ($plan->current_subscribers > 0) {
                $plan->decrement('current_subscribers');
            }
            $statusValue = $plan->status instanceof \Modules\SubscriptionManager\Enums\SubscriptionPlanStatus
                ? $plan->status->value
                : $plan->status;

            if ($statusValue === \Modules\SubscriptionManager\Enums\SubscriptionPlanStatus::COMPLETED->value && $plan->current_subscribers < $plan->max_subscribers) {
                $plan->update(['status' => \Modules\SubscriptionManager\Enums\SubscriptionPlanStatus::ACTIVE->value]);
            }
        }
    }

    /**
     * Update an existing player subscription and synchronize invoice & payments.
     */
    public function updateSubscription(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $subscription = $this->subscriptionRepository->find($id);

            $oldPaidAmount = (float) $subscription->paid_amount;
            $oldTotalAmount = (float) $subscription->total_amount;

            // 1. Calculate new total amount if plan_id or offer_id changes
            if (!empty($data['plan_id']) && $data['plan_id'] != $subscription->plan_id) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::findOrFail($data['plan_id']);
                $totalAmount = (float) $plan->base_price;
                $data['total_amount'] = $totalAmount;
            } elseif (!empty($data['offer_id']) && $data['offer_id'] != $subscription->offer_id) {
                $offer = \Modules\SubscriptionManager\Models\Offer::findOrFail($data['offer_id']);
                $totalAmount = (float) $offer->price;
                $data['total_amount'] = $totalAmount;
            } else {
                $totalAmount = $oldTotalAmount;
            }

            // 2. Financials Calculation
            $paidAmount = array_key_exists('paid_amount', $data) ? (float) $data['paid_amount'] : $oldPaidAmount;
            $remainingAmount = max(0, $totalAmount - $paidAmount);
            $data['remaining_amount'] = $remainingAmount;

            // 3. Update subscription model
            $subscription = $this->subscriptionRepository->update($id, $data);
            $subscription->refresh();

            // 4. Synchronize linked Invoice
            $memberDTO = $this->memberSharedService->getMemberById($subscription->member_id);
            $branchId = $memberDTO->branchId;

            $invoice = \Modules\SubscriptionManager\Models\Invoice::firstOrCreate(
                ['player_subscription_id' => $subscription->id],
                [
                    'member_id' => $subscription->member_id,
                    'branch_id' => $branchId,
                    'total' => $totalAmount,
                    'status' => 'unpaid',
                ]
            );

            $invoice->update([
                'member_id' => $subscription->member_id,
                'total' => $totalAmount,
                'status' => $remainingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid'),
            ]);

            // 5. If paid_amount increased, record new Payment for the difference
            if ($paidAmount > $oldPaidAmount) {
                $addedAmount = $paidAmount - $oldPaidAmount;

                if ($branchId) {
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
                        'amount' => $addedAmount,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'status' => 'completed',
                    ]);

                    event(new \Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded($payment));
                }
            }

            $subscription->member = $memberDTO;

            return $subscription;
        });
    }
}

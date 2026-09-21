<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Modules\SubscriptionManager\Models\PlayerSubscription;

class AllSubscriptionsReportService
{
    /**
     * Get comprehensive report for all subscriptions without pagination.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        app(\Modules\SubscriptionManager\Services\SubscriptionService::class)->syncExpiredSubscriptions();

        $status        = $filters['status'] ?? 'all';
        $planId        = $filters['plan_id'] ?? null;
        $paymentStatus = $filters['payment_status'] ?? 'all';
        $coachId       = $filters['coach_id'] ?? null;
        $branchId      = $filters['branch_id'] ?? null;
        $startDate     = $filters['start_date'] ?? null;
        $endDate       = $filters['end_date'] ?? null;
        $search        = $filters['search'] ?? null;
        $currency      = $filters['currency'] ?? null;

        $query = PlayerSubscription::query()
            ->with([
                'member.person.contacts',
                'member.person.user',
                'member.branch',
                'plan.branch',
                'plan.planActivities.staffActivity.activity.activityType',
                'plan.planActivities.staffActivity.staff.person',
                'offer',
                'items',
                'payments.safe.account',
            ]);

        // 0. Filter by Currency
        if (!empty($currency)) {
            $query->where('currency', $currency);
        }

        // 1. Filter by Subscription Status
        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        // 2. Filter by Subscription Plan
        if ($planId) {
            $query->where('plan_id', $planId);
        }

        // 3. Filter by Payment Status
        if ($paymentStatus !== 'all' && !empty($paymentStatus)) {
            if ($paymentStatus === 'paid') {
                $query->whereRaw('paid_amount >= total_amount');
            } elseif ($paymentStatus === 'partially_paid') {
                $query->whereRaw('paid_amount > 0 AND paid_amount < total_amount');
            } elseif ($paymentStatus === 'unpaid') {
                $query->whereRaw('paid_amount = 0');
            }
        }

        // 4. Filter by Assigned Coach
        if ($coachId) {
            $query->whereHas('plan.planActivities.staffActivity', fn($q) => $q->where('staff_id', $coachId));
        }

        // 5. Filter by Branch
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereHas('plan', fn($pq) => $pq->where('branch_id', $branchId))
                  ->orWhereHas('member', fn($mq) => $mq->where('branch_id', $branchId));
            });
        }

        // 6. Direct Date Range Filter
        if ($startDate) {
            $query->whereDate('start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('start_date', '<=', $endDate);
        }

        // 7. Search Filter (member number, full name, username, phone number from contacts)
        if (!empty($search)) {
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('member_number', 'like', "%{$search}%")
                   ->orWhereHas('person', function ($pq) use ($search) {
                       $pq->where('full_name', 'like', "%{$search}%")
                          ->orWhereHas('user', function ($uq) use ($search) {
                              $uq->where('username', 'like', "%{$search}%")
                                ->orWhere('custom_username', 'like', "%{$search}%");
                          })
                          ->orWhereHas('contacts', function ($cq) use ($search) {
                              $cq->where('phone_number', 'like', "%{$search}%");
                          });
                   });
            });
        }

        // 8. Daily Entry Exclusion Filter
        $excludeDaily = filter_var($filters['exclude_daily_entry'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($excludeDaily) {
            $query->whereDoesntHave('plan.planActivities.staffActivity.activity.activityType', function ($q) {
                $q->where('is_daily_entry', true);
            })->whereDoesntHave('plan', function ($q) {
                $q->where('session_count', 1)
                  ->where(function ($nq) {
                      $nq->where('name', 'like', '%دخولية%')
                         ->orWhere('name', 'like', '%دخول يومي%')
                         ->orWhere('name', 'like', '%daily%');
                  });
            });
        }

        // Retrieve all matching records
        $allSubscriptions = $query->orderBy('id', 'desc')->get();

        // Calculate summary statistics
        $dailyEntryCount = $allSubscriptions->filter(fn($s) => $s->plan?->isDailyEntryPlan() ?? false)->count();
        $summary = [
            'total_subscriptions'  => $allSubscriptions->count(),
            'total_revenue'        => round((float) $allSubscriptions->sum('total_amount'), 2),
            'total_paid'           => round((float) $allSubscriptions->sum('paid_amount'), 2),
            'total_remaining'      => round((float) $allSubscriptions->sum(fn($s) => max(0, (float)$s->total_amount - (float)$s->paid_amount)), 2),
            'active_count'         => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'active')->count(),
            'finished_count'       => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'finished')->count(),
            'daily_entry_count'    => $dailyEntryCount,
            'frozen_count'         => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'frozen')->count(),
            'terminated_count'     => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'terminated')->count(),
            'fully_paid_count'     => $allSubscriptions->filter(fn($s) => (float)$s->paid_amount >= (float)$s->total_amount)->count(),
            'partially_paid_count' => $allSubscriptions->filter(fn($s) => (float)$s->paid_amount > 0 && (float)$s->paid_amount < (float)$s->total_amount)->count(),
            'unpaid_count'         => $allSubscriptions->filter(fn($s) => (float)$s->paid_amount == 0)->count(),
            'currency'             => $currency ?? 'SYP',
            'currency_type'        => $currency ?? 'SYP',
        ];

        // Format records
        $records = $allSubscriptions->map(function ($sub) {
            $member = $sub->member;
            $person = $member?->person;
            $user   = $person?->user;

            // Safe & Account name resolution
            $safePayment = $sub->payments->first(fn($p) => !empty($p->safe_id));
            $accountName = $safePayment?->safe?->account?->name ?? $safePayment?->safe?->name ?? null;
            $safeName    = $safePayment?->safe?->name ?? null;
            $username    = $user?->username ?? $user?->custom_username ?? null;
            $isDailyEntry = $sub->plan?->isDailyEntryPlan() ?? false;

            // Collect all contact phone numbers
            $phoneNumbers = $person?->contacts
                ?->pluck('phone_number')
                ->filter()
                ->unique()
                ->values()
                ->toArray() ?? [];

            if (empty($phoneNumbers) && !empty($person?->mobile1)) {
                $phoneNumbers[] = $person->mobile1;
            }

            $memberPhone = !empty($phoneNumbers) ? implode(' - ', $phoneNumbers) : '';

            // Detailed member contacts array
            $memberContacts = $person?->contacts?->map(function ($c) {
                return [
                    'id'           => $c->id,
                    'name'         => $c->name,
                    'phone_number' => $c->phone_number,
                    'relation'     => $c->relation,
                ];
            })->values()->toArray() ?? [];

            // Financial Calculations
            $totalAmount     = (float) $sub->total_amount;
            $paidAmount      = (float) $sub->paid_amount;
            $remainingAmount = max(0, $totalAmount - $paidAmount);
            $isFullyPaid     = $paidAmount >= $totalAmount;

            $paymentStatus = match (true) {
                $isFullyPaid     => 'paid',
                $paidAmount > 0  => 'partially_paid',
                default          => 'unpaid',
            };

            $paymentStatusLabel = match ($paymentStatus) {
                'paid'           => 'مدفوع بالكامل',
                'partially_paid' => 'مدفوع جزئياً',
                'unpaid'         => 'غير مدفوع',
            };

            $planActivities = $sub->plan ? $sub->plan->planActivities : collect();

            // Coaches list across subscription plan activities
            $coachesList = $planActivities
                ->map(fn($pa) => $pa->staffActivity?->staff?->person?->full_name)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $coachesNames = !empty($coachesList) ? implode('، ', $coachesList) : 'لا يوجد مدرب مسند';

            // Sessions breakdown across activities
            $totalAllocated = 0;
            $totalConsumed  = 0;
            $hasUnlimited   = false;

            $itemsBreakdown = $sub->items->values()->map(function ($item, $index) use ($planActivities, &$totalAllocated, &$totalConsumed, &$hasUnlimited) {
                $planActivity = $planActivities->get($index);
                $staffActivity = $planActivity?->staffActivity;
                $activity = $staffActivity?->activity;
                $coach = $staffActivity?->staff;

                if ($item->is_unlimited) {
                    $hasUnlimited = true;
                }
                $allocated = (int) $item->sessions_allocated;
                $consumed  = (int) $item->sessions_consumed;
                $remaining = $item->is_unlimited ? null : max(0, $allocated - $consumed);

                $totalAllocated += $allocated;
                $totalConsumed  += $consumed;

                $coachPerson = $coach?->person;
                $coachFullName = $coachPerson?->full_name ?? 'غير مسند';
                $nameParts = explode(' ', trim($coachFullName));
                $firstName = $coachPerson?->first_name ?? ($nameParts[0] ?? $coachFullName);
                $lastName  = $coachPerson?->last_name ?? (count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '');

                return [
                    'item_id'            => $item->id,
                    'activity_name'      => $activity?->name ?? 'نشاط عام',
                    'coach_name'         => $coachFullName,
                    'coach'              => $coach ? [
                        'id'         => $coach->id,
                        'name'       => $coachFullName,
                        'full_name'  => $coachFullName,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                    ] : null,
                    'is_unlimited'       => (bool) $item->is_unlimited,
                    'sessions_allocated' => $allocated,
                    'sessions_consumed'  => $consumed,
                    'sessions_remaining' => $remaining,
                ];
            });

            $totalRemaining = $hasUnlimited ? null : max(0, $totalAllocated - $totalConsumed);

            return [
                'subscription_id'      => $sub->id,
                'member_id'            => $sub->member_id,
                'member_number'        => $member?->member_number ?? '',
                'member_name'          => $person?->full_name ?? 'غير محدد',
                'username'             => $username,
                'member_phone'         => $memberPhone,
                'member_contacts'      => $memberContacts,
                'branch_name'          => $sub->plan?->branch?->name ?? $member?->branch?->name ?? 'غير محدد',

                // Safe & Accounting Account Details
                'account_name'         => $accountName,
                'safe_name'            => $safeName,

                // Plan & Offer Details
                'plan_id'              => $sub->plan_id,
                'plan_name'            => $sub->plan?->name ?? '',
                'plan_type'            => $sub->plan?->type ?? '',
                'is_daily_entry'       => $isDailyEntry,
                'offer_name'           => $sub->offer?->title ?? null,

                // Dates & Status
                'status'               => is_object($sub->status) ? $sub->status->value : $sub->status,
                'status_label'         => is_object($sub->status) && method_exists($sub->status, 'label') ? $sub->status->label() : $sub->status,
                'start_date'           => $sub->start_date?->format('Y-m-d'),
                'end_date'             => $sub->end_date?->format('Y-m-d'),
                'created_at'           => $sub->created_at?->format('Y-m-d H:i:s'),

                // Payments & Financial Metrics
                'total_amount'         => round($totalAmount, 2),
                'paid_amount'          => round($paidAmount, 2),
                'remaining_amount'     => round($remainingAmount, 2),
                'currency'             => $sub->currency ?? ($sub->plan?->currency ?? 'SYP'),
                'currency_type'        => $sub->currency ?? ($sub->plan?->currency ?? 'SYP'),
                'is_fully_paid'        => $isFullyPaid,
                'payment_status'       => $paymentStatus,
                'payment_status_label' => $paymentStatusLabel,

                // Sessions & Coaches Summary
                'coaches_list'         => $coachesList,
                'coaches_names'        => $coachesNames,
                'sessions_summary'     => [
                    'is_unlimited'     => $hasUnlimited,
                    'total_allocated'  => $totalAllocated,
                    'total_consumed'   => $totalConsumed,
                    'total_remaining'  => $totalRemaining,
                ],
                'items'                => $itemsBreakdown,
                'notes'                => $sub->notes,
            ];
        });

        return [
            'summary' => $summary,
            'records' => $records,
        ];
    }
}

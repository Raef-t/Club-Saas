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
        $status        = $filters['status'] ?? 'all';
        $planId        = $filters['plan_id'] ?? null;
        $paymentStatus = $filters['payment_status'] ?? 'all';
        $coachId       = $filters['coach_id'] ?? null;
        $branchId      = $filters['branch_id'] ?? null;
        $startDate     = $filters['start_date'] ?? null;
        $endDate       = $filters['end_date'] ?? null;
        $search        = $filters['search'] ?? null;

        $query = PlayerSubscription::query()
            ->with([
                'member.person.contacts',
                'member.branch',
                'plan.branch',
                'offer',
                'items.activity',
                'items.coach.person',
            ]);

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
            $query->whereHas('items', fn($q) => $q->where('coach_id', $coachId));
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

        // 7. Search Filter (member number, full name, phone number from contacts)
        if (!empty($search)) {
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('member_number', 'like', "%{$search}%")
                   ->orWhereHas('person', function ($pq) use ($search) {
                       $pq->where('full_name', 'like', "%{$search}%")
                          ->orWhereHas('contacts', function ($cq) use ($search) {
                              $cq->where('phone_number', 'like', "%{$search}%");
                          });
                   });
            });
        }

        // Retrieve all matching records
        $allSubscriptions = $query->orderBy('id', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_subscriptions'  => $allSubscriptions->count(),
            'total_revenue'        => round((float) $allSubscriptions->sum('total_amount'), 2),
            'total_paid'           => round((float) $allSubscriptions->sum('paid_amount'), 2),
            'total_remaining'      => round((float) $allSubscriptions->sum(fn($s) => max(0, (float)$s->total_amount - (float)$s->paid_amount)), 2),
            'active_count'         => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'active')->count(),
            'finished_count'       => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'finished')->count(),
            'frozen_count'         => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'frozen')->count(),
            'terminated_count'     => $allSubscriptions->filter(fn($s) => (is_object($s->status) ? $s->status->value : $s->status) === 'terminated')->count(),
            'fully_paid_count'     => $allSubscriptions->filter(fn($s) => (float)$s->paid_amount >= (float)$s->total_amount)->count(),
            'partially_paid_count' => $allSubscriptions->filter(fn($s) => (float)$s->paid_amount > 0 && (float)$s->paid_amount < (float)$s->total_amount)->count(),
            'unpaid_count'         => $allSubscriptions->filter(fn($s) => (float)$s->paid_amount == 0)->count(),
        ];

        // Format records
        $records = $allSubscriptions->map(function ($sub) {
            $member = $sub->member;
            $person = $member?->person;

            // Extract phone number from contacts relation
            $phone = $person?->contacts?->first()?->phone_number
                ?? $person?->mobile1
                ?? '';

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

            // Coaches list across subscription items
            $coachesList = $sub->items
                ->map(fn($item) => $item->coach?->person?->full_name)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $coachesNames = !empty($coachesList) ? implode('، ', $coachesList) : 'لا يوجد مدرب مسند';

            // Sessions breakdown across activities
            $totalAllocated = 0;
            $totalConsumed  = 0;
            $hasUnlimited   = false;

            $itemsBreakdown = $sub->items->map(function ($item) use (&$totalAllocated, &$totalConsumed, &$hasUnlimited) {
                if ($item->is_unlimited) {
                    $hasUnlimited = true;
                }
                $allocated = (int) $item->sessions_allocated;
                $consumed  = (int) $item->sessions_consumed;
                $remaining = $item->is_unlimited ? null : max(0, $allocated - $consumed);

                $totalAllocated += $allocated;
                $totalConsumed  += $consumed;

                return [
                    'item_id'            => $item->id,
                    'activity_name'      => $item->activity?->name ?? 'نشاط عام',
                    'coach_name'         => $item->coach?->person?->full_name ?? 'غير مسند',
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
                'member_phone'         => $phone,
                'branch_name'          => $sub->plan?->branch?->name ?? $member?->branch?->name ?? 'غير محدد',

                // Plan & Offer Details
                'plan_id'              => $sub->plan_id,
                'plan_name'            => $sub->plan?->name ?? '',
                'plan_type'            => $sub->plan?->type ?? '',
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

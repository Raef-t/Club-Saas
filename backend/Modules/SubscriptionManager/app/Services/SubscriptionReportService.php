<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Models\PlayerSubscription;

class SubscriptionReportService
{
    /**
     * Get report for expired & renewed subscriptions with detailed metrics.
     *
     * @param array $filters
     * @return array
     */
    public function getRenewalStatusReport(array $filters = []): array
    {
        $type      = $filters['type'] ?? 'all'; // 'expired_non_renewed', 'renewed', 'all'
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;
        $branchId  = $filters['branch_id'] ?? null;
        $planId    = $filters['plan_id'] ?? null;
        $coachId   = $filters['coach_id'] ?? null;
        $search    = $filters['search'] ?? null;

        // Base query for subscriptions
        $query = PlayerSubscription::query()
            ->with([
                'member.person',
                'member.branch',
                'plan.branch',
                'items.activity',
                'items.coach.person',
            ]);

        // Branch filter
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereHas('plan', fn($pq) => $pq->where('branch_id', $branchId))
                  ->orWhereHas('member', fn($mq) => $mq->where('branch_id', $branchId));
            });
        }

        // Plan filter
        if ($planId) {
            $query->where('plan_id', $planId);
        }

        // Coach filter
        if ($coachId) {
            $query->whereHas('items', fn($q) => $q->where('coach_id', $coachId));
        }

        // Search filter (Member Name, Phone, Member Number)
        if (!empty($search)) {
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('member_number', 'like', "%{$search}%")
                   ->orWhereHas('person', function ($pq) use ($search) {
                       $pq->where('full_name', 'like', "%{$search}%")
                          ->orWhere('mobile1', 'like', "%{$search}%");
                   });
            });
        }

        $dateFilterBy = $filters['date_filter_by'] ?? 'end_date';
        $dateColumn   = match ($dateFilterBy) {
            'start_date'   => 'start_date',
            'created_date' => 'created_at',
            default        => 'end_date',
        };

        // Date filter on subscription date range
        if ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        $allSubscriptions = $query->orderBy('end_date', 'desc')->get();

        // Process data into renewal categories
        $reportRecords = [];
        $totalExpiredNonRenewed = 0;
        $totalRenewed = 0;
        $totalLostRevenue = 0.0;
        $totalRenewedRevenue = 0.0;

        foreach ($allSubscriptions as $sub) {
            $member = $sub->member;
            if (!$member) {
                continue;
            }

            // Create a non-mutating copy of end_date for query calculations
            $endDateCopy = $sub->end_date ? $sub->end_date->copy()->subDays(7) : null;

            // Check if this member has a subsequent subscription starting after/near this end_date or created after
            $nextSubscription = PlayerSubscription::where('member_id', $sub->member_id)
                ->where('id', '!=', $sub->id)
                ->where(function ($q) use ($sub, $endDateCopy) {
                    $q->where('id', '>', $sub->id);
                    if ($endDateCopy) {
                        $q->orWhere('start_date', '>=', $endDateCopy);
                    }
                })
                ->with(['plan'])
                ->orderBy('start_date', 'asc')
                ->first();

            $isRenewed = !is_null($nextSubscription);

            // Filter according to requested 'type'
            if ($type === 'expired_non_renewed' && $isRenewed) {
                continue;
            }
            if ($type === 'renewed' && !$isRenewed) {
                continue;
            }

            // Extract Coaches & Activities
            $coaches = [];
            $activities = [];
            foreach ($sub->items as $item) {
                if ($item->activity) {
                    $activityName = is_array($item->activity->name)
                        ? ($item->activity->name['ar'] ?? reset($item->activity->name))
                        : $item->activity->name;
                    $activities[] = $activityName;
                }
                if ($item->coach && $item->coach->person) {
                    $coaches[] = [
                        'id'   => $item->coach->id,
                        'name' => $item->coach->person->full_name,
                    ];
                }
            }
            $coaches = array_values(array_unique($coaches, SORT_REGULAR));
            $coachesNames = implode(', ', array_column($coaches, 'name'));

            $person = $member->person;
            $daysSinceExpiration = $sub->end_date ? (int) now()->diffInDays($sub->end_date, false) : 0;

            if ($isRenewed) {
                $totalRenewed++;
                $totalRenewedRevenue += (float) ($nextSubscription->total_amount ?? 0);
            } else {
                $totalExpiredNonRenewed++;
                $totalLostRevenue += (float) ($sub->total_amount ?? 0);
            }

            $planName = $sub->plan ? (is_array($sub->plan->name) ? ($sub->plan->name['ar'] ?? reset($sub->plan->name)) : $sub->plan->name) : 'N/A';
            $statusValue = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;

            $record = [
                'subscription_id'       => $sub->id,
                'status_type'           => $isRenewed ? 'renewed' : 'expired_non_renewed',
                'status_label'          => $isRenewed ? 'تم التجديد' : 'منتهي ولم يجدد',
                
                // Member details
                'member_id'             => $member->id,
                'member_number'         => $member->member_number,
                'member_name'           => $person->full_name ?? 'N/A',
                'member_phone'          => $person->mobile1 ?? 'N/A',
                'branch_name'           => $member->branch->name ?? ($sub->plan->branch->name ?? 'N/A'),
                
                // Plan & Coaches
                'plan_id'               => $sub->plan_id,
                'plan_name'             => $planName,
                'plan_type'             => $sub->plan->type ?? 'N/A',
                'coaches'               => $coaches,
                'coaches_names'         => $coachesNames ?: 'لا يوجد مدرب محدد',
                'activities'            => array_values(array_unique($activities)),

                // Subscription Timeline & Status
                'start_date'            => $sub->start_date ? $sub->start_date->format('Y-m-d') : null,
                'end_date'              => $sub->end_date ? $sub->end_date->format('Y-m-d') : null,
                'subscription_status'   => $statusValue,
                'days_since_expiration' => $daysSinceExpiration < 0 ? abs($daysSinceExpiration) : 0,

                // Financial Details
                'total_amount'          => (float) $sub->total_amount,
                'paid_amount'           => (float) $sub->paid_amount,
                'remaining_amount'      => (float) $sub->remaining_amount,
                'is_fully_paid'         => $sub->is_fully_paid,

                // Renewal Details (if renewed)
                'renewal_info'          => $isRenewed ? [
                    'renewed_subscription_id' => $nextSubscription->id,
                    'new_plan_name'           => $nextSubscription->plan ? (is_array($nextSubscription->plan->name) ? ($nextSubscription->plan->name['ar'] ?? reset($nextSubscription->plan->name)) : $nextSubscription->plan->name) : 'N/A',
                    'renewal_date'            => $nextSubscription->start_date ? $nextSubscription->start_date->format('Y-m-d') : null,
                    'new_end_date'            => $nextSubscription->end_date ? $nextSubscription->end_date->format('Y-m-d') : null,
                    'new_total_amount'        => (float) $nextSubscription->total_amount,
                ] : null,
            ];

            $reportRecords[] = $record;
        }

        $totalRecords = count($reportRecords);
        $renewalRate = ($totalExpiredNonRenewed + $totalRenewed) > 0
            ? round(($totalRenewed / ($totalExpiredNonRenewed + $totalRenewed)) * 100, 2)
            : 0;

        return [
            'summary' => [
                'total_records'                => $totalRecords,
                'total_expired_non_renewed'    => $totalExpiredNonRenewed,
                'total_renewed'                => $totalRenewed,
                'renewal_rate_percentage'      => $renewalRate,
                'total_lost_potential_revenue'  => $totalLostRevenue,
                'total_renewed_revenue'        => $totalRenewedRevenue,
            ],
            'records' => $reportRecords,
        ];
    }
}

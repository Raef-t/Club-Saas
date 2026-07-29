<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Modules\SubscriptionManager\Models\PlayerSubscription;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;

class FrozenAndTerminatedReportService
{
    /**
     * Get report for frozen and terminated subscriptions with detailed reasons & timelines.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        $statusFilter = $filters['status'] ?? 'all';
        $startDate    = $filters['start_date'] ?? null;
        $endDate      = $filters['end_date'] ?? null;
        $dateFilterBy = $filters['date_filter_by'] ?? 'event_date';
        $branchId     = $filters['branch_id'] ?? null;
        $planId       = $filters['plan_id'] ?? null;
        $search       = $filters['search'] ?? null;

        $query = PlayerSubscription::query()
            ->with([
                'member.person',
                'member.branch',
                'plan.branch',
                'freezes',
            ]);

        // Filter status
        if ($statusFilter === 'frozen') {
            $query->where('status', PlayerSubscriptionStatus::FROZEN->value);
        } elseif ($statusFilter === 'terminated') {
            $query->where('status', PlayerSubscriptionStatus::TERMINATED->value);
        } else {
            $query->whereIn('status', [
                PlayerSubscriptionStatus::FROZEN->value,
                PlayerSubscriptionStatus::TERMINATED->value,
            ]);
        }

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

        // Search filter
        if (!empty($search)) {
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('member_number', 'like', "%{$search}%")
                    ->orWhereHas('person', function ($pq) use ($search) {
                        $pq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('mobile1', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($startDate || $endDate) {
            if ($dateFilterBy === 'event_date') {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where(function ($fq) use ($startDate, $endDate) {
                        $fq->where('status', PlayerSubscriptionStatus::FROZEN->value)
                            ->whereHas('freezes', function ($frq) use ($startDate, $endDate) {
                                if ($startDate) $frq->whereDate('freeze_start_date', '>=', $startDate);
                                if ($endDate)   $frq->whereDate('freeze_start_date', '<=', $endDate);
                            });
                    })
                        ->orWhere(function ($tq) use ($startDate, $endDate) {
                            $tq->where('status', PlayerSubscriptionStatus::TERMINATED->value);
                            if ($startDate) $tq->whereDate('updated_at', '>=', $startDate);
                            if ($endDate)   $tq->whereDate('updated_at', '<=', $endDate);
                        });
                });
            } else {
                $dateColumn = match ($dateFilterBy) {
                    'start_date'   => 'start_date',
                    'created_date' => 'created_at',
                    default        => 'end_date',
                };
                if ($startDate) $query->whereDate($dateColumn, '>=', $startDate);
                if ($endDate)   $query->whereDate($dateColumn, '<=', $endDate);
            }
        }

        $subscriptions = $query->latest('updated_at')->get();

        $records = [];
        $totalFrozen = 0;
        $totalTerminated = 0;
        $totalFrozenRevenue = 0.0;
        $totalLostTerminatedRevenue = 0.0;

        foreach ($subscriptions as $sub) {
            $member = $sub->member;
            if (!$member) continue;

            /** @var PlayerSubscriptionStatus|null $statusEnum */
            $statusEnum = $sub->status instanceof PlayerSubscriptionStatus
                ? $sub->status
                : PlayerSubscriptionStatus::tryFrom((string) $sub->status);

            $statusStr   = $statusEnum ? $statusEnum->value : (string) $sub->status;
            $statusLabel = $statusEnum ? $statusEnum->label() : $statusStr;

            $freezeDetails = null;
            $terminationDetails = null;

            if ($statusStr === 'frozen') {
                $totalFrozen++;
                $totalFrozenRevenue += (float) $sub->total_amount;

                $latestFreeze = $sub->freezes->sortByDesc('created_at')->first();
                if ($latestFreeze) {
                    $freezeDetails = [
                        'freeze_id'           => $latestFreeze->id,
                        'freeze_start_date'   => $latestFreeze->freeze_start_date ? $latestFreeze->freeze_start_date->format('Y-m-d') : null,
                        'freeze_end_date'     => $latestFreeze->freeze_end_date ? $latestFreeze->freeze_end_date->format('Y-m-d') : null,
                        'actual_end_date'     => $latestFreeze->actual_end_date ? $latestFreeze->actual_end_date->format('Y-m-d') : null,
                        'reason'              => $latestFreeze->reason ?? 'لا يوجد سبب مدون',
                        'is_currently_frozen' => is_null($latestFreeze->actual_end_date),
                    ];
                }
            } elseif ($statusStr === 'terminated') {
                $totalTerminated++;
                $totalLostTerminatedRevenue += (float) $sub->total_amount;

                $reason = 'لا يوجد سبب مدون';
                if (!empty($sub->notes)) {
                    if (str_contains($sub->notes, 'Cancellation reason:')) {
                        $parts = explode('Cancellation reason:', $sub->notes);
                        $reason = trim(end($parts));
                    } else {
                        $reason = $sub->notes;
                    }
                }

                $terminationDetails = [
                    'terminated_at' => $sub->updated_at ? $sub->updated_at->format('Y-m-d H:i:s') : null,
                    'reason'        => $reason,
                    'notes'         => $sub->notes,
                ];
            }

            $planName = $sub->plan ? (is_array($sub->plan->name) ? ($sub->plan->name['ar'] ?? reset($sub->plan->name)) : $sub->plan->name) : 'N/A';

            $records[] = [
                'subscription_id'     => $sub->id,
                'status'              => $statusStr,
                'status_label'        => $statusLabel,
                'member_id'           => $member->id,
                'member_number'       => $member->member_number,
                'member_name'         => $member->person->full_name ?? 'N/A',
                'member_phone'        => $member->person->mobile1 ?? 'N/A',
                'branch_name'         => $member->branch->name ?? ($sub->plan->branch->name ?? 'N/A'),
                'plan_id'             => $sub->plan_id,
                'plan_name'           => $planName,
                'plan_type'           => $sub->plan->type ?? 'N/A',
                'start_date'          => $sub->start_date ? $sub->start_date->format('Y-m-d') : null,
                'end_date'            => $sub->end_date ? $sub->end_date->format('Y-m-d') : null,
                'total_amount'        => (float) $sub->total_amount,
                'paid_amount'         => (float) $sub->paid_amount,
                'remaining_amount'    => (float) $sub->remaining_amount,
                'is_fully_paid'       => $sub->is_fully_paid,
                'freeze_details'      => $freezeDetails,
                'termination_details' => $terminationDetails,
            ];
        }

        return [
            'summary' => [
                'total_records'                 => count($records),
                'total_frozen'                  => $totalFrozen,
                'total_terminated'              => $totalTerminated,
                'total_frozen_revenue'          => $totalFrozenRevenue,
                'total_lost_terminated_revenue' => $totalLostTerminatedRevenue,
            ],
            'records' => $records,
        ];
    }
}

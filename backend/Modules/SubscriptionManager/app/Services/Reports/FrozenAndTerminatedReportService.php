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
                'member.person.user',
                'member.person.contacts',
                'member.branch',
                'plan.branch',
                'plan.planActivities.staffActivity.staff.person',
                'plan.planActivities.staffActivity.activity',
                'freezes',
                'payments.safe.account',
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
                            ->orWhere('mobile1', 'like', "%{$search}%")
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

            $person = $member->person;
            $user   = $person?->user;
            $username = $user?->username ?? $user?->custom_username ?? null;
            $customUsername = $user?->custom_username ?? null;

            // Safe & Account name resolution
            $safePayment = $sub->payments->first(fn($p) => !empty($p->safe_id));
            $accountName = $safePayment?->safe?->account?->name ?? $safePayment?->safe?->name ?? $username;
            $safeName    = $safePayment?->safe?->name ?? null;

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

            $memberPhone = !empty($phoneNumbers) ? implode(' - ', $phoneNumbers) : ($person?->mobile1 ?? 'N/A');

            // Detailed member contacts array
            $contactPersons = $person?->contacts?->map(function ($c) {
                return [
                    'id'           => $c->id,
                    'name'         => $c->name,
                    'phone_number' => $c->phone_number,
                    'relation'     => $c->relation,
                ];
            })->values()->toArray() ?? [];

            if (empty($contactPersons) && !empty($person?->mobile1)) {
                $contactPersons[] = [
                    'id'           => null,
                    'name'         => $person->full_name ?? 'الرقم الرئيسي',
                    'phone_number' => $person->mobile1,
                    'relation'     => 'صاحب الاشتراك',
                ];
            }

            // Coaches & Activities across subscription plan
            $coaches = [];
            $activities = [];
            $planActivities = $sub->plan ? $sub->plan->planActivities : collect();
            foreach ($planActivities as $planActivity) {
                $staffActivity = $planActivity->staffActivity;
                $activity = $staffActivity?->activity;
                $coach = $staffActivity?->staff;

                if ($activity) {
                    $activityName = is_array($activity->name)
                        ? ($activity->name['ar'] ?? reset($activity->name))
                        : $activity->name;
                    $activities[] = $activityName;
                }
                if ($coach && $coach->person) {
                    $coaches[] = [
                        'id'   => $coach->id,
                        'name' => $coach->person->full_name,
                    ];
                }
            }
            $coaches = array_values(array_unique($coaches, SORT_REGULAR));
            $coachesNames = !empty($coaches) ? implode(', ', array_column($coaches, 'name')) : 'لا يوجد مدرب محدد';
            $activities = array_values(array_unique($activities));

            /** @var PlayerSubscriptionStatus|null $statusEnum */
            $statusEnum = $sub->status instanceof PlayerSubscriptionStatus
                ? $sub->status
                : PlayerSubscriptionStatus::tryFrom((string) $sub->status);

            $statusStr   = $statusEnum ? $statusEnum->value : (string) $sub->status;
            $statusLabel = $statusEnum ? $statusEnum->label() : $statusStr;

            $freezeDetails = null;
            $terminationDetails = null;
            $reason = 'لا يوجد سبب مدون';
            $eventDate = null;
            $frozenDays = 0;

            if ($statusStr === 'frozen') {
                $totalFrozen++;
                $totalFrozenRevenue += (float) $sub->total_amount;

                $latestFreeze = $sub->freezes->sortByDesc('created_at')->first();
                $freezeReason = !empty($latestFreeze?->reason) ? trim($latestFreeze->reason) : null;
                $reason = $freezeReason ?: (!empty($sub->reason) ? trim($sub->reason) : 'لا يوجد سبب مدون');

                $freezeStartDate = $latestFreeze?->freeze_start_date ? $latestFreeze->freeze_start_date->format('Y-m-d') : null;
                $freezeEndDate   = $latestFreeze?->freeze_end_date ? $latestFreeze->freeze_end_date->format('Y-m-d') : null;
                $actualEndDate   = $latestFreeze?->actual_end_date ? $latestFreeze->actual_end_date->format('Y-m-d') : null;
                $eventDate       = $freezeStartDate ?? ($sub->updated_at ? $sub->updated_at->format('Y-m-d') : null);

                if ($latestFreeze && $latestFreeze->freeze_start_date) {
                    $endCalc = $latestFreeze->actual_end_date ?? $latestFreeze->freeze_end_date ?? now();
                    $frozenDays = max(0, (int) $latestFreeze->freeze_start_date->diffInDays($endCalc));
                }

                $freezeDetails = [
                    'freeze_id'           => $latestFreeze?->id,
                    'freeze_start_date'   => $freezeStartDate,
                    'freeze_end_date'     => $freezeEndDate,
                    'actual_end_date'     => $actualEndDate,
                    'frozen_days'         => $frozenDays,
                    'reason'              => $reason,
                    'is_currently_frozen' => $latestFreeze ? is_null($latestFreeze->actual_end_date) : true,
                ];
            } elseif ($statusStr === 'terminated') {
                $totalTerminated++;
                $totalLostTerminatedRevenue += (float) $sub->total_amount;

                $terminationReason = null;
                if (!empty($sub->reason)) {
                    $terminationReason = trim($sub->reason);
                } elseif (!empty($sub->notes)) {
                    if (str_contains($sub->notes, 'Cancellation reason:')) {
                        $parts = explode('Cancellation reason:', $sub->notes);
                        $extracted = trim(end($parts));
                        if (!empty($extracted)) {
                            $terminationReason = $extracted;
                        }
                    } elseif (str_contains($sub->notes, 'سبب الإلغاء:')) {
                        $parts = explode('سبب الإلغاء:', $sub->notes);
                        $extracted = trim(end($parts));
                        if (!empty($extracted)) {
                            $terminationReason = $extracted;
                        }
                    } else {
                        $terminationReason = trim($sub->notes);
                    }
                }

                $reason = !empty($terminationReason) ? $terminationReason : 'لا يوجد سبب مدون';
                $eventDate = $sub->updated_at ? $sub->updated_at->format('Y-m-d H:i:s') : null;

                $terminationDetails = [
                    'terminated_at' => $eventDate,
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
                'member_name'         => $person->full_name ?? 'N/A',
                'username'            => $username,
                'custom_username'     => $customUsername,
                'account_name'        => $accountName,
                'safe_name'           => $safeName,
                'member_phone'        => $memberPhone,
                'contact_persons'     => $contactPersons,
                'member_contacts'     => $contactPersons,
                'member'              => [
                    'id'              => $member->id,
                    'member_number'   => $member->member_number,
                    'full_name'       => $person->full_name ?? 'N/A',
                    'username'        => $username,
                    'custom_username' => $customUsername,
                    'phone'           => $memberPhone,
                ],
                'branch_name'         => $member->branch->name ?? ($sub->plan->branch->name ?? 'N/A'),
                'plan_id'             => $sub->plan_id,
                'plan_name'           => $planName,
                'plan_type'           => $sub->plan->type ?? 'N/A',
                'coaches'             => $coaches,
                'coaches_names'       => $coachesNames,
                'activities'          => $activities,
                'start_date'          => $sub->start_date ? $sub->start_date->format('Y-m-d') : null,
                'end_date'            => $sub->end_date ? $sub->end_date->format('Y-m-d') : null,
                'event_date'          => $eventDate,
                'reason'              => $reason,
                'freeze_reason'       => $statusStr === 'frozen' ? $reason : null,
                'termination_reason'  => $statusStr === 'terminated' ? $reason : null,
                'cancellation_reason' => $statusStr === 'terminated' ? $reason : null,
                'frozen_days'         => $frozenDays,
                'freeze_start_date'   => $statusStr === 'frozen' ? ($freezeDetails['freeze_start_date'] ?? null) : null,
                'freeze_end_date'     => $statusStr === 'frozen' ? ($freezeDetails['freeze_end_date'] ?? null) : null,
                'total_amount'        => (float) $sub->total_amount,
                'paid_amount'         => (float) $sub->paid_amount,
                'remaining_amount'    => (float) $sub->remaining_amount,
                'currency'            => $sub->currency ?? ($sub->plan?->currency ?? 'SYP'),
                'currency_type'       => $sub->currency ?? ($sub->plan?->currency ?? 'SYP'),
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

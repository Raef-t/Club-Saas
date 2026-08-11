<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\SubscriptionManager\Models\PlayerSubscription;

class RenewalReportService
{
    /**
     * Get report for expired & renewed subscriptions with detailed metrics.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        $type      = $filters['type'] ?? 'all';
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;
        $branchId  = $filters['branch_id'] ?? null;
        $planId    = $filters['plan_id'] ?? null;
        $coachId   = $filters['coach_id'] ?? null;
        $search    = $filters['search'] ?? null;

        $query = PlayerSubscription::query()
            ->with([
                'member.person.contacts',
                'member.branch',
                'plan.branch',
                'plan.planActivities.staffActivity.activity',
                'plan.planActivities.staffActivity.staff.person',
                'items',
            ]);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereHas('plan', fn($pq) => $pq->where('branch_id', $branchId))
                  ->orWhereHas('member', fn($mq) => $mq->where('branch_id', $branchId));
            });
        }

        if ($planId) {
            $query->where('plan_id', $planId);
        }

        if ($coachId) {
            $query->whereHas('plan.planActivities.staffActivity', fn($q) => $q->where('staff_id', $coachId));
        }

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

        if ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        $allSubscriptions = $query->orderBy('end_date', 'desc')->get();

        // Batch load last attendance for members
        $memberIds = $allSubscriptions->pluck('member_id')->filter()->unique();
        $lastAttendances = [];
        if ($memberIds->isNotEmpty()) {
            $lastAttendances = DB::table('attendances')
                ->whereIn('attendable_id', $memberIds)
                ->where('attendable_type', 'member')
                ->select('attendable_id', DB::raw('MAX(check_in_at) as last_check_in'))
                ->groupBy('attendable_id')
                ->pluck('last_check_in', 'attendable_id')
                ->toArray();
        }

        $reportRecords = [];
        $totalExpiredNonRenewed = 0;
        $totalRenewed = 0;
        $totalLostRevenue = 0.0;
        $totalRenewedRevenue = 0.0;

        $memberIds = $allSubscriptions->pluck('member_id')->filter()->unique();
        $nextSubscriptionsGrouped = PlayerSubscription::whereIn('member_id', $memberIds)
            ->with(['plan'])
            ->orderBy('start_date', 'asc')
            ->get()
            ->groupBy('member_id');

        foreach ($allSubscriptions as $sub) {
            $member = $sub->member;
            if (!$member) {
                continue;
            }

            $endDateCopy = $sub->end_date ? $sub->end_date->copy()->subDays(7) : null;

            $memberSubs = $nextSubscriptionsGrouped->get($sub->member_id, collect());
            $nextSubscription = $memberSubs->first(function ($item) use ($sub, $endDateCopy) {
                if ($item->id === $sub->id) {
                    return false;
                }
                if ($item->id > $sub->id) {
                    return true;
                }
                if ($endDateCopy && $item->start_date && $item->start_date >= $endDateCopy) {
                    return true;
                }
                return false;
            });

            $isRenewed = !is_null($nextSubscription);

            if ($type === 'expired_non_renewed' && $isRenewed) {
                continue;
            }
            if ($type === 'renewed' && !$isRenewed) {
                continue;
            }

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
            $coachesNames = implode(', ', array_column($coaches, 'name'));

            $person = $member->person;
            $daysSinceExpiration = $sub->end_date ? (int) now()->diffInDays($sub->end_date, false) : 0;

            // Extract contact persons
            $contactPersons = [];
            if ($person && $person->contacts) {
                foreach ($person->contacts as $c) {
                    $contactPersons[] = [
                        'id'           => $c->id,
                        'name'         => $c->name,
                        'phone_number' => $c->phone_number,
                        'relation'     => $c->relation,
                    ];
                }
            }
            if (empty($contactPersons) && !empty($person?->mobile1)) {
                $contactPersons[] = [
                    'id'           => null,
                    'name'         => $person->full_name ?? 'الرقم الرئيسي',
                    'phone_number' => $person->mobile1,
                    'relation'     => 'صاحب الاشتراك',
                ];
            }

            // Calculate absence period
            $lastAttendanceRaw = $lastAttendances[$member->id] ?? null;
            if ($lastAttendanceRaw) {
                $lastDate = Carbon::parse($lastAttendanceRaw);
                $diff = $lastDate->diff(now());
                $totalAbsenceDays = (int) $lastDate->diffInDays(now());
                $absencePeriod = [
                    'last_attendance_date' => $lastDate->format('Y-m-d H:i:s'),
                    'years'                => $diff->y,
                    'months'               => $diff->m,
                    'days'                 => $diff->d,
                    'total_days'           => $totalAbsenceDays,
                    'formatted'            => "{$diff->y} سنة، {$diff->m} شهر، {$diff->d} يوم",
                ];
            } else {
                $absencePeriod = [
                    'last_attendance_date' => null,
                    'years'                => null,
                    'months'               => null,
                    'days'                 => null,
                    'total_days'           => null,
                    'formatted'            => 'لم يحضر أبدًا',
                ];
            }

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
                'member_id'             => $member->id,
                'member_number'         => $member->member_number,
                'member_name'           => $person->full_name ?? 'N/A',
                'member_phone'          => $person->mobile1 ?? 'N/A',
                'contact_persons'       => $contactPersons,
                'absence_period'        => $absencePeriod,
                'branch_name'           => $member->branch->name ?? ($sub->plan->branch->name ?? 'N/A'),
                'plan_id'               => $sub->plan_id,
                'plan_name'             => $planName,
                'plan_type'             => $sub->plan->type ?? 'N/A',
                'coaches'               => $coaches,
                'coaches_names'         => $coachesNames ?: 'لا يوجد مدرب محدد',
                'activities'            => array_values(array_unique($activities)),
                'start_date'            => $sub->start_date ? $sub->start_date->format('Y-m-d') : null,
                'end_date'              => $sub->end_date ? $sub->end_date->format('Y-m-d') : null,
                'subscription_status'   => $statusValue,
                'days_since_expiration' => $daysSinceExpiration < 0 ? abs($daysSinceExpiration) : 0,
                'total_amount'          => (float) $sub->total_amount,
                'paid_amount'           => (float) $sub->paid_amount,
                'remaining_amount'      => (float) $sub->remaining_amount,
                'is_fully_paid'         => $sub->is_fully_paid,
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

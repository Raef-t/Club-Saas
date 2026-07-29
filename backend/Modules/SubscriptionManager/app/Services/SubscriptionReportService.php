<?php

namespace Modules\SubscriptionManager\Services;

use Modules\SubscriptionManager\Models\PlayerSubscription;
use Illuminate\Support\Facades\DB;

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

    /**
     * Get report for session templates, plans, and subscriber counts filtered by time slot.
     *
     * @param array $filters
     * @return array
     */
    public function getTimeSlotCapacityReport(array $filters = []): array
    {
        $startTime = $filters['start_time'] ?? null;
        $endTime   = $filters['end_time'] ?? null;
        $dayOfWeek = $filters['day_of_week'] ?? null;
        $branchId  = $filters['branch_id'] ?? null;
        $planId    = $filters['plan_id'] ?? null;

        $daysMap = [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];

        $query = DB::table('sport_session_templates as sst')
            ->join('subscription_plans as sp', 'sp.id', '=', 'sst.plan_id')
            ->leftJoin('facilities as f', 'f.id', '=', 'sst.facility_id')
            ->where('sst.is_active', true);

        if ($startTime) {
            $query->whereTime('sst.start_time', '>=', $startTime);
        }
        if ($endTime) {
            $query->whereTime('sst.end_time', '<=', $endTime);
        }
        if ($dayOfWeek) {
            $query->where('sst.day_of_week', $dayOfWeek);
        }
        if ($branchId) {
            $query->where('sp.branch_id', $branchId);
        }
        if ($planId) {
            $query->where('sp.id', $planId);
        }

        $templates = $query->select(
            'sst.id as session_template_id',
            'sst.plan_id',
            'sp.name as plan_name',
            'sp.type as plan_type',
            'sst.day_of_week',
            'sst.start_time',
            'sst.end_time',
            'f.name as facility_name'
        )->orderBy('sst.day_of_week')->orderBy('sst.start_time')->get();

        $records = [];
        $totalActiveSubscribersSum = 0;
        $totalPresentSum = 0;
        $uniquePlanIds = [];

        foreach ($templates as $tmpl) {
            $planName = json_decode($tmpl->plan_name, true) ?? $tmpl->plan_name;
            if (is_array($planName)) {
                $planName = $planName['ar'] ?? reset($planName);
            }

            // Active members subscribed to this plan
            $activeSubscribersCount = DB::table('player_subscriptions')
                ->where('plan_id', $tmpl->plan_id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now())
                ->distinct('member_id')
                ->count('member_id');

            // Currently checked-in players in this plan
            $presentPlayersCount = DB::table('attendances as att')
                ->join('attendance_consumptions as ac', 'ac.attendance_id', '=', 'att.id')
                ->where('ac.subscription_plan_id', $tmpl->plan_id)
                ->where('att.attendable_type', 'member')
                ->where('att.status', 'checked_in')
                ->whereNull('att.check_out_at')
                ->distinct('att.attendable_id')
                ->count('att.attendable_id');

            $uniquePlanIds[$tmpl->plan_id] = true;
            $totalActiveSubscribersSum += $activeSubscribersCount;
            $totalPresentSum += $presentPlayersCount;

            $records[] = [
                'session_template_id'      => $tmpl->session_template_id,
                'plan_id'                  => $tmpl->plan_id,
                'plan_name'                => $planName,
                'plan_type'                => $tmpl->plan_type,
                'day_of_week'              => $tmpl->day_of_week,
                'day_name'                 => $daysMap[$tmpl->day_of_week] ?? 'غير محدد',
                'start_time'               => $tmpl->start_time,
                'end_time'                 => $tmpl->end_time,
                'facility_name'            => $tmpl->facility_name ?? 'الصالّة العامة',
                'active_subscribers_count' => $activeSubscribersCount,
                'present_players_count'    => $presentPlayersCount,
            ];
        }

        return [
            'summary' => [
                'total_matching_sessions'  => count($records),
                'total_unique_plans'       => count($uniquePlanIds),
                'total_active_subscribers' => $totalActiveSubscribersSum,
                'total_currently_present'  => $totalPresentSum,
            ],
            'records' => $records,
        ];
    }

    /**
     * Get report for peak and off-peak attendance hours excluding branch holidays.
     *
     * @param array $filters
     * @return array
     */
    public function getPeakHoursReport(array $filters = []): array
    {
        $startDate = $filters['start_date'] ?? now()->subDays(30)->format('Y-m-d');
        $endDate   = $filters['end_date'] ?? now()->format('Y-m-d');
        $branchId  = !empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $type      = $filters['attendable_type'] ?? 'member';

        $daysMap = [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];

        // 1. Fetch branch holidays (both specific dates and weekly day-offs)
        $specificHolidays = DB::table('branch_holidays')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('type', 'specific_dates')
            ->get(['start_date', 'end_date', 'reason']);

        $weeklyHolidays = DB::table('branch_holidays')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('type', 'weekly')
            ->pluck('day_of_week') // 0 = Sun, 1 = Mon ... 6 = Sat
            ->toArray();

        // Convert weekly holiday days (0-6) to MySQL DAYOFWEEK format (1=Sun ... 7=Sat)
        $sqlWeeklyHolidays = array_map(fn($d) => $d + 1, $weeklyHolidays);

        // 2. Base query for attendances with holiday exclusion
        $baseQuery = DB::table('attendances')
            ->where('attendable_type', $type)
            ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        }

        // Exclude specific holiday date ranges
        foreach ($specificHolidays as $sh) {
            if ($sh->start_date && $sh->end_date) {
                $baseQuery->whereNotBetween(DB::raw('DATE(check_in_at)'), [$sh->start_date, $sh->end_date]);
            }
        }

        // Exclude weekly holiday days
        if (!empty($sqlWeeklyHolidays)) {
            $baseQuery->whereNotIn(DB::raw('DAYOFWEEK(check_in_at)'), $sqlWeeklyHolidays);
        }

        // 3. Hourly traffic aggregation (0-23)
        $hourlyData = (clone $baseQuery)
            ->select(DB::raw('HOUR(check_in_at) as hour'), DB::raw('COUNT(*) as total_count'))
            ->groupBy(DB::raw('HOUR(check_in_at)'))
            ->pluck('total_count', 'hour')
            ->toArray();

        $hourlyBreakdown = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyBreakdown[] = [
                'hour'  => $h,
                'label' => sprintf('%02d:00', $h),
                'count' => $hourlyData[$h] ?? 0,
            ];
        }

        $sortedHours = collect($hourlyBreakdown)->sortByDesc('count')->values();
        $topPeakHours = $sortedHours->take(3)->toArray();
        $topOffPeakHours = collect($hourlyBreakdown)->filter(fn($item) => $item['count'] > 0)->sortBy('count')->take(3)->values()->toArray();

        // 4. Daily traffic aggregation (0=Sunday ... 6=Saturday)
        $dailyData = (clone $baseQuery)
            ->select(DB::raw('DAYOFWEEK(check_in_at) - 1 as day_num'), DB::raw('COUNT(*) as total_count'))
            ->groupBy(DB::raw('DAYOFWEEK(check_in_at) - 1'))
            ->pluck('total_count', 'day_num')
            ->toArray();

        $dailyBreakdown = [];
        foreach ($daysMap as $num => $name) {
            $isWeeklyHoliday = in_array($num, $weeklyHolidays);

            $dailyBreakdown[] = [
                'day_number'        => $num,
                'day_name'          => $name,
                'total_check_ins'   => $dailyData[$num] ?? 0,
                'is_weekly_holiday' => $isWeeklyHoliday,
            ];
        }

        $validDailyBreakdown = collect($dailyBreakdown)->reject(fn($d) => $d['is_weekly_holiday']);
        $busiestDay  = $validDailyBreakdown->sortByDesc('total_check_ins')->first()['day_name'] ?? 'غير محدد';
        $quietestDay = $validDailyBreakdown->sortBy('total_check_ins')->first()['day_name'] ?? 'غير محدد';

        return [
            'summary' => [
                'busiest_day'                => $busiestDay,
                'quietest_day'               => $quietestDay,
                'peak_hours_range'           => isset($topPeakHours[0]) ? $topPeakHours[0]['label'] : 'N/A',
                'off_peak_hours_range'       => isset($topOffPeakHours[0]) ? $topOffPeakHours[0]['label'] : 'N/A',
                'total_attendances_analyzed' => array_sum($hourlyData),
                'excluded_holidays_count'    => count($specificHolidays),
            ],
            'top_peak_hours'     => $topPeakHours,
            'top_off_peak_hours' => $topOffPeakHours,
            'hourly_breakdown'   => $hourlyBreakdown,
            'daily_breakdown'    => $dailyBreakdown,
            'specific_holidays'  => $specificHolidays,
        ];
    }
}

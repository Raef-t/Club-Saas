<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Illuminate\Support\Facades\DB;

class PeakHoursReportService
{
    /**
     * Get report for peak and off-peak attendance hours excluding branch holidays.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
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

        $specificHolidays = DB::table('branch_holidays')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('type', 'specific_dates')
            ->get(['start_date', 'end_date', 'reason']);

        $weeklyHolidays = DB::table('branch_holidays')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('type', 'weekly')
            ->pluck('day_of_week')
            ->toArray();

        $sqlWeeklyHolidays = array_map(fn($d) => $d + 1, $weeklyHolidays);

        $baseQuery = DB::table('attendances')
            ->where('attendable_type', $type)
            ->whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
        }

        foreach ($specificHolidays as $sh) {
            if ($sh->start_date && $sh->end_date) {
                $baseQuery->whereNotBetween(DB::raw('DATE(check_in_at)'), [$sh->start_date, $sh->end_date]);
            }
        }

        if (!empty($sqlWeeklyHolidays)) {
            $baseQuery->whereNotIn(DB::raw('DAYOFWEEK(check_in_at)'), $sqlWeeklyHolidays);
        }

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

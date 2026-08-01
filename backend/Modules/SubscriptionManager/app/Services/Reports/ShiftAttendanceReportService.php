<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Illuminate\Support\Facades\DB;

class ShiftAttendanceReportService
{
    /**
     * Get report for shift attendance and crowd analytics for activities tied to shifts.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        // 1. Determine date range based on filters (specific date, month, or custom start/end range)
        if (!empty($filters['date'])) {
            $startDate = $filters['date'] . ' 00:00:00';
            $endDate   = $filters['date'] . ' 23:59:59';
            $periodLabel = 'يوم ' . $filters['date'];
        } elseif (!empty($filters['month'])) {
            $startDate = $filters['month'] . '-01 00:00:00';
            $endDate   = date('Y-m-t 23:59:59', strtotime($startDate));
            $periodLabel = 'شهر ' . $filters['month'];
        } else {
            $startDateStr = !empty($filters['start_date']) ? $filters['start_date'] : now()->startOfMonth()->format('Y-m-d');
            $endDateStr   = !empty($filters['end_date']) ? $filters['end_date'] : now()->endOfMonth()->format('Y-m-d');
            $startDate = $startDateStr . ' 00:00:00';
            $endDate   = $endDateStr . ' 23:59:59';
            $periodLabel = 'من ' . $startDateStr . ' إلى ' . $endDateStr;
        }

        $branchId   = !empty($filters['branch_id']) ? (int) $filters['branch_id'] : null;
        $activityId = !empty($filters['activity_id']) ? (int) $filters['activity_id'] : null;
        $shiftId    = !empty($filters['shift_id']) ? (int) $filters['shift_id'] : null;

        $daysMap = [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت'
        ];

        // 2. Build shifts query
        $shiftsQuery = DB::table('branch_shifts as bs')
            ->join('branches as b', 'bs.branch_id', '=', 'b.id')
            ->select(
                'bs.id',
                'bs.name as shift_name',
                'bs.start_time',
                'bs.end_time',
                'bs.day_of_week',
                'bs.branch_id',
                'b.name as branch_name'
            );

        if ($branchId) {
            $shiftsQuery->where('bs.branch_id', $branchId);
        }

        if ($shiftId) {
            $shiftsQuery->where('bs.id', $shiftId);
        }

        $shifts = $shiftsQuery->get();

        // 3. Optional plan IDs matching activity_id if filtered
        $matchingPlanIds = null;
        if ($activityId) {
            $matchingPlanIds = DB::table('plan_activities as pa')
                ->join('staff_activities as sa', 'pa.staff_activity_id', '=', 'sa.id')
                ->where('sa.activity_id', $activityId)
                ->pluck('pa.plan_id')
                ->toArray();
        }

        // 4. Calculate attendance per shift
        $records = [];
        $totalAllAttendances = 0;

        foreach ($shifts as $shift) {
            $attendanceQuery = DB::table('attendances as a')
                ->where(function ($q) {
                    $q->where('a.attendable_type', 'like', '%Member%')
                      ->orWhere('a.attendable_type', 'member');
                })
                ->whereBetween('a.check_in_at', [$startDate, $endDate])
                ->where('a.branch_id', $shift->branch_id)
                ->whereRaw('TIME(a.check_in_at) >= ?', [$shift->start_time])
                ->whereRaw('TIME(a.check_in_at) <= ?', [$shift->end_time]);

            if ($shift->day_of_week !== null) {
                // In MySQL DAYOFWEEK: 1 = Sunday, 2 = Monday ... 7 = Saturday
                $attendanceQuery->whereRaw('(DAYOFWEEK(a.check_in_at) - 1) = ?', [$shift->day_of_week]);
            }

            if ($matchingPlanIds !== null) {
                $attendanceQuery->whereIn('a.id', function ($sub) use ($matchingPlanIds) {
                    $sub->select('attendance_id')
                        ->from('attendance_consumptions')
                        ->whereIn('subscription_plan_id', $matchingPlanIds);
                });
            }

            $totalAttended = (clone $attendanceQuery)->count();
            $uniquePlayers = (clone $attendanceQuery)->distinct('a.attendable_id')->count('a.attendable_id');

            $totalAllAttendances += $totalAttended;

            $records[] = [
                'shift_id'               => $shift->id,
                'shift_name'             => $shift->shift_name,
                'branch_id'              => $shift->branch_id,
                'branch_name'            => $shift->branch_name,
                'day_of_week'            => $shift->day_of_week,
                'day_name'               => $shift->day_of_week !== null ? ($daysMap[$shift->day_of_week] ?? 'كل الأيام') : 'كل الأيام',
                'start_time'             => $shift->start_time,
                'end_time'               => $shift->end_time,
                'attended_players_count' => $totalAttended,
                'unique_players_count'   => $uniquePlayers,
            ];
        }

        // 5. Calculate crowd percentage and determine busiest & quietest shift
        foreach ($records as &$rec) {
            $rec['crowd_percentage'] = $totalAllAttendances > 0 
                ? round(($rec['attended_players_count'] / $totalAllAttendances) * 100, 2) 
                : 0;
        }

        $sortedByAttendance = collect($records)->sortByDesc('attended_players_count')->values();
        $busiestShift  = $sortedByAttendance->first();
        $quietestShift = $sortedByAttendance->where('attended_players_count', '>', 0)->last() ?? $sortedByAttendance->last();

        return [
            'summary' => [
                'period_label'            => $periodLabel,
                'total_shift_attendances' => $totalAllAttendances,
                'total_shifts_count'      => count($records),
                'busiest_shift'           => $busiestShift ? [
                    'shift_id'               => $busiestShift['shift_id'],
                    'shift_name'             => $busiestShift['shift_name'],
                    'branch_name'            => $busiestShift['branch_name'],
                    'attended_players_count' => $busiestShift['attended_players_count'],
                    'crowd_percentage'       => $busiestShift['crowd_percentage'] . '%',
                ] : null,
                'quietest_shift'          => $quietestShift ? [
                    'shift_id'               => $quietestShift['shift_id'],
                    'shift_name'             => $quietestShift['shift_name'],
                    'branch_name'            => $quietestShift['branch_name'],
                    'attended_players_count' => $quietestShift['attended_players_count'],
                    'crowd_percentage'       => $quietestShift['crowd_percentage'] . '%',
                ] : null,
            ],
            'records' => $records,
        ];
    }
}

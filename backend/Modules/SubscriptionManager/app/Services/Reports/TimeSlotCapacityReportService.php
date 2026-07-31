<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Illuminate\Support\Facades\DB;

class TimeSlotCapacityReportService
{
    /**
     * Get report grouped by activity -> coach (staff_activity) -> plan -> session schedules and active subscriber counts.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        $startTime  = $filters['start_time'] ?? null;
        $endTime    = $filters['end_time'] ?? null;
        $dayOfWeek  = isset($filters['day_of_week']) && $filters['day_of_week'] !== '' ? (int) $filters['day_of_week'] : null;
        $branchId   = $filters['branch_id'] ?? null;
        $planId     = $filters['plan_id'] ?? null;
        $activityId = $filters['activity_id'] ?? null;

        $daysMap = [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];

        // Query joining plan_activities -> staff_activities -> activities & staff & subscription_plans
        $query = DB::table('plan_activities as pa')
            ->join('staff_activities as sa', 'sa.id', '=', 'pa.staff_activity_id')
            ->join('activities as a', 'a.id', '=', 'sa.activity_id')
            ->join('staff as st', 'st.id', '=', 'sa.staff_id')
            ->join('people as p', 'p.id', '=', 'st.person_id')
            ->join('subscription_plans as sp', 'sp.id', '=', 'pa.plan_id')
            ->where('sp.is_active', true);

        if ($branchId) {
            $query->where('sp.branch_id', $branchId);
        }
        if ($planId) {
            $query->where('sp.id', $planId);
        }
        if ($activityId) {
            $query->where('a.id', $activityId);
        }

        if ($startTime || $endTime || $dayOfWeek !== null) {
            $query->whereExists(function ($subQuery) use ($startTime, $endTime, $dayOfWeek) {
                $subQuery->select(DB::raw(1))
                    ->from('sport_session_templates as sst')
                    ->whereColumn('sst.plan_id', 'sp.id')
                    ->where('sst.is_active', true);

                if ($startTime) {
                    $subQuery->whereTime('sst.start_time', '>=', $startTime);
                }
                if ($endTime) {
                    $subQuery->whereTime('sst.end_time', '<=', $endTime);
                }
                if ($dayOfWeek !== null) {
                    $subQuery->where('sst.day_of_week', $dayOfWeek);
                }
            });
        }

        $rows = $query->select(
            'a.id as activity_id',
            'a.name as activity_name',
            'st.id as staff_id',
            'p.full_name as coach_name',
            'sa.id as staff_activity_id',
            'sp.id as plan_id',
            'sp.name as plan_name',
            'sp.type as plan_type'
        )->distinct()->get();

        $activitiesGrouped = [];
        $uniqueActivities = [];
        $uniqueCoaches = [];
        $uniquePlans = [];

        $planSubscriberCounts = [];
        $planSchedules = [];

        foreach ($rows as $row) {
            $actId      = $row->activity_id;
            $staffId    = $row->staff_id;
            $staffActId = $row->staff_activity_id;
            $pId        = $row->plan_id;

            $uniqueActivities[$actId] = true;
            $uniqueCoaches[$staffId]   = true;
            $uniquePlans[$pId]         = true;

            $actName = json_decode($row->activity_name, true) ?? $row->activity_name;
            if (is_array($actName)) {
                $actName = $actName['ar'] ?? reset($actName);
            }

            $planName = json_decode($row->plan_name, true) ?? $row->plan_name;
            if (is_array($planName)) {
                $planName = $planName['ar'] ?? reset($planName);
            }

            if (!isset($activitiesGrouped[$actId])) {
                $activitiesGrouped[$actId] = [
                    'activity_id'   => $actId,
                    'activity_name' => $actName,
                    'coaches'       => [],
                ];
            }

            if (!isset($activitiesGrouped[$actId]['coaches'][$staffActId])) {
                $activitiesGrouped[$actId]['coaches'][$staffActId] = [
                    'staff_id'          => $staffId,
                    'coach_name'        => $row->coach_name,
                    'staff_activity_id' => $staffActId,
                    'plans'             => [],
                ];
            }

            if (!isset($planSubscriberCounts[$pId])) {
                $planSubscriberCounts[$pId] = DB::table('player_subscriptions')
                    ->where('plan_id', $pId)
                    ->where('status', 'active')
                    ->whereDate('end_date', '>=', now())
                    ->distinct('member_id')
                    ->count('member_id');
            }

            if (!isset($planSchedules[$pId])) {
                $sessionQuery = DB::table('sport_session_templates as sst')
                    ->leftJoin('facilities as f', 'f.id', '=', 'sst.facility_id')
                    ->where('sst.plan_id', $pId)
                    ->where('sst.is_active', true);

                if ($startTime) {
                    $sessionQuery->whereTime('sst.start_time', '>=', $startTime);
                }
                if ($endTime) {
                    $sessionQuery->whereTime('sst.end_time', '<=', $endTime);
                }
                if ($dayOfWeek !== null) {
                    $sessionQuery->where('sst.day_of_week', $dayOfWeek);
                }

                $sessions = $sessionQuery->select(
                    'sst.id as session_template_id',
                    'sst.day_of_week',
                    'sst.start_time',
                    'sst.end_time',
                    'f.name as facility_name'
                )->orderBy('sst.day_of_week')->orderBy('sst.start_time')->get();

                $schedules = [];
                foreach ($sessions as $s) {
                    $schedules[] = [
                        'session_template_id' => $s->session_template_id,
                        'day_of_week'         => $s->day_of_week,
                        'day_name'            => $daysMap[$s->day_of_week] ?? 'غير محدد',
                        'start_time'          => $s->start_time,
                        'end_time'            => $s->end_time,
                        'facility_name'       => $s->facility_name ?? 'الصالة العامة',
                    ];
                }

                $planSchedules[$pId] = $schedules;
            }

            if (!isset($activitiesGrouped[$actId]['coaches'][$staffActId]['plans'][$pId])) {
                $activitiesGrouped[$actId]['coaches'][$staffActId]['plans'][$pId] = [
                    'plan_id'                  => $pId,
                    'plan_name'                => $planName,
                    'plan_type'                => $row->plan_type,
                    'active_subscribers_count' => $planSubscriberCounts[$pId],
                    'schedules'                => $planSchedules[$pId],
                ];
            }
        }

        $activities = [];
        foreach ($activitiesGrouped as $actData) {
            $coachesList = [];
            foreach ($actData['coaches'] as $coachData) {
                $coachData['plans'] = array_values($coachData['plans']);
                $coachesList[] = $coachData;
            }
            $actData['coaches'] = $coachesList;
            $activities[] = $actData;
        }

        $totalActiveSubscribersSum = 0;
        foreach (array_keys($uniquePlans) as $matchedPlanId) {
            $totalActiveSubscribersSum += $planSubscriberCounts[$matchedPlanId] ?? 0;
        }

        return [
            'summary' => [
                'total_activities'         => count($uniqueActivities),
                'total_coaches'            => count($uniqueCoaches),
                'total_plans'              => count($uniquePlans),
                'total_active_subscribers' => $totalActiveSubscribersSum,
            ],
            'activities' => $activities,
        ];
    }
}


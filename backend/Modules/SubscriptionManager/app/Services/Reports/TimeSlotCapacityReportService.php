<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Illuminate\Support\Facades\DB;

class TimeSlotCapacityReportService
{
    /**
     * Get report for session templates, plans, and subscriber counts filtered by time slot.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        $startTime = $filters['start_time'] ?? null;
        $endTime   = $filters['end_time'] ?? null;
        $dayOfWeek = isset($filters['day_of_week']) && $filters['day_of_week'] !== '' ? (int) $filters['day_of_week'] : null;
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
        if ($dayOfWeek !== null) {
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
        $uniquePlanIds = [];

        foreach ($templates as $tmpl) {
            $planName = json_decode($tmpl->plan_name, true) ?? $tmpl->plan_name;
            if (is_array($planName)) {
                $planName = $planName['ar'] ?? reset($planName);
            }

            $coaches = DB::table('plan_activities as pa')
                ->join('staff_activities as sa', 'sa.id', '=', 'pa.staff_activity_id')
                ->join('staff as st', 'st.id', '=', 'sa.staff_id')
                ->join('people as p', 'p.id', '=', 'st.person_id')
                ->where('pa.plan_id', $tmpl->plan_id)
                ->select('st.id', 'p.full_name as name')
                ->distinct()
                ->get()
                ->toArray();

            $coachesNames = !empty($coaches) 
                ? implode(', ', array_column($coaches, 'name')) 
                : 'لا يوجد مدرب محدد';

            $activeSubscribersCount = DB::table('player_subscriptions')
                ->where('plan_id', $tmpl->plan_id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now())
                ->distinct('member_id')
                ->count('member_id');

            $uniquePlanIds[$tmpl->plan_id] = true;
            $totalActiveSubscribersSum += $activeSubscribersCount;

            $records[] = [
                'session_template_id'      => $tmpl->session_template_id,
                'plan_id'                  => $tmpl->plan_id,
                'plan_name'                => $planName,
                'plan_type'                => $tmpl->plan_type,
                'coaches_names'            => $coachesNames,
                'coaches'                  => $coaches,
                'day_of_week'              => $tmpl->day_of_week,
                'day_name'                 => $daysMap[$tmpl->day_of_week] ?? 'غير محدد',
                'start_time'               => $tmpl->start_time,
                'end_time'                 => $tmpl->end_time,
                'facility_name'            => $tmpl->facility_name ?? 'الصالّة العامة',
                'active_subscribers_count' => $activeSubscribersCount,
            ];
        }

        return [
            'summary' => [
                'total_matching_sessions'  => count($records),
                'total_unique_plans'       => count($uniquePlanIds),
                'total_active_subscribers' => $totalActiveSubscribersSum,
            ],
            'records' => $records,
        ];
    }
}

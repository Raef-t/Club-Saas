<?php

namespace Modules\SubscriptionManager\Services\Reports;

use Illuminate\Support\Facades\DB;
use Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus;

class CoachSubscriptionReportService
{
    /**
     * Get report for Group Session Coaches and General Equipment active players.
     *
     * @param array $filters
     * @return array
     */
    public function getReport(array $filters = []): array
    {
        $branchId = $filters['branch_id'] ?? null;

        // 1. Group Session Coaches (حصة جماعية)
        $groupQuery = DB::table('staff as st')
            ->join('people as p', 'p.id', '=', 'st.person_id')
            ->join('staff_activities as sa', 'sa.staff_id', '=', 'st.id')
            ->join('activities as a', 'a.id', '=', 'sa.activity_id')
            ->leftJoin('activity_types as at', 'at.id', '=', 'a.activity_type_id')
            ->join('plan_activities as pa', 'pa.staff_activity_id', '=', 'sa.id')
            ->join('subscription_plans as sp', 'sp.id', '=', 'pa.plan_id')
            ->where('st.role', 'coach')
            ->where(function ($q) {
                $q->where('at.is_session_based', true)
                  ->orWhere('at.name', 'like', '%حصة جماعية%');
            })
            ->whereNull('st.deleted_at')
            ->whereNull('sa.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereNull('pa.deleted_at')
            ->whereNull('sp.deleted_at');

        if ($branchId) {
            $groupQuery->where('sp.branch_id', $branchId);
        }

        $groupRows = $groupQuery->select(
            'st.id as coach_id',
            'p.full_name as coach_name',
            'a.id as activity_id',
            'a.name as activity_name',
            'sp.id as plan_id'
        )->distinct()->get();

        // Calculate active players per plan for group session plans
        $groupPlanIds = $groupRows->pluck('plan_id')->unique()->filter()->toArray();
        $groupPlanActiveCounts = [];

        if (!empty($groupPlanIds)) {
            $groupPlanActiveCounts = DB::table('player_subscriptions')
                ->select('plan_id', DB::raw('COUNT(DISTINCT member_id) as active_count'))
                ->whereIn('plan_id', $groupPlanIds)
                ->where('status', PlayerSubscriptionStatus::ACTIVE->value)
                ->whereNull('deleted_at')
                ->groupBy('plan_id')
                ->pluck('active_count', 'plan_id')
                ->toArray();
        }

        // Aggregate Group Session Coaches Data
        $coachesMap = [];
        $totalGroupActivePlayersSum = 0;

        foreach ($groupRows as $row) {
            $cId   = $row->coach_id;
            $actId = $row->activity_id;
            $pId   = $row->plan_id;

            if (!isset($coachesMap[$cId])) {
                $coachesMap[$cId] = [
                    'coach_id'             => $cId,
                    'coach_name'           => $row->coach_name,
                    'activities_map'       => [],
                    'plans_map'            => [],
                    'active_players_count' => 0,
                ];
            }

            // Unify Activity Name
            $actName = json_decode($row->activity_name, true) ?? $row->activity_name;
            if (is_array($actName)) {
                $actName = $actName['ar'] ?? $actName['en'] ?? reset($actName);
            }
            $coachesMap[$cId]['activities_map'][$actId] = $actName;

            // Track plan to count active members once per coach
            if (!isset($coachesMap[$cId]['plans_map'][$pId])) {
                $coachesMap[$cId]['plans_map'][$pId] = true;
                $activeForPlan = (int) ($groupPlanActiveCounts[$pId] ?? 0);
                $coachesMap[$cId]['active_players_count'] += $activeForPlan;
            }
        }

        $groupCoachesList = [];
        foreach ($coachesMap as $cData) {
            $groupCoachesList[] = [
                'coach_id'             => $cData['coach_id'],
                'coach_name'           => $cData['coach_name'],
                'activities'           => array_values($cData['activities_map']),
                'active_players_count' => $cData['active_players_count'],
            ];
            $totalGroupActivePlayersSum += $cData['active_players_count'];
        }

        // 2. General Equipment Active Players (أجهزة عامة / تدريب عام)
        $generalEquipQuery = DB::table('player_subscriptions as ps')
            ->join('subscription_plans as sp', 'sp.id', '=', 'ps.plan_id')
            ->join('plan_activities as pa', 'pa.plan_id', '=', 'sp.id')
            ->join('staff_activities as sa', 'sa.id', '=', 'pa.staff_activity_id')
            ->join('activities as a', 'a.id', '=', 'sa.activity_id')
            ->join('activity_types as at', 'at.id', '=', 'a.activity_type_id')
            ->where('ps.status', PlayerSubscriptionStatus::ACTIVE->value)
            ->where('at.name', 'like', '%تدريب عام%')
            ->whereNull('ps.deleted_at')
            ->whereNull('sp.deleted_at')
            ->whereNull('pa.deleted_at')
            ->whereNull('sa.deleted_at')
            ->whereNull('a.deleted_at');

        if ($branchId) {
            $generalEquipQuery->where('sp.branch_id', $branchId);
        }

        $generalEquipmentActiveCount = $generalEquipQuery->count(DB::raw('DISTINCT ps.member_id'));

        return [
            'summary' => [
                'total_group_coaches'               => count($groupCoachesList),
                'total_group_active_players'        => $totalGroupActivePlayersSum,
                'general_equipment_active_players' => $generalEquipmentActiveCount,
            ],
            'group_session_coaches' => $groupCoachesList,
            'general_equipment'     => [
                'title'                => 'أجهزة عامة',
                'activity_type_name'   => 'تدريب عام',
                'active_players_count' => $generalEquipmentActiveCount,
            ],
        ];
    }
}

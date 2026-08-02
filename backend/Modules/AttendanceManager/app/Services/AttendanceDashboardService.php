<?php

namespace Modules\AttendanceManager\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceDashboardService
{
    /**
     * Get aggregated stats for the reception dashboard.
     *
     * @param int|null $branchId
     * @return array
     */
    public function getDashboardStats(?int $branchId = null): array
    {
        return [
            'total_active_subscribed_members'    => $this->getActiveSubscribedMembersCount($branchId),
            'realtime_training_players_count'    => $this->getRealtimeTrainingPlayersCount($branchId),
            'expiring_subscriptions_count'       => $this->getExpiringSubscriptionsCount($branchId),
            'free_assigned_player_lockers_count' => $this->getFreeAssignedLockersCount($branchId),
            'current_active_session_plans'       => $this->getCurrentActiveSessionPlans($branchId),
        ];
    }

    /**
     * Get cached aggregated stats for the reception dashboard.
     *
     * @param int|null $branchId
     * @param int $ttlSeconds
     * @return array
     */
    /**
     * Get cached aggregated stats for the reception dashboard.
     *
     * @param int|null $branchId
     * @param int $ttlSeconds
     * @return array
     */
    public function getCachedDashboardStats(?int $branchId = null, int $ttlSeconds = 60): array
    {
        $branchKey = $branchId ? (string) $branchId : 'all';
        $minuteKey = now()->format('YmdHi');
        $cacheKey  = "dashboard_stats_cache_{$branchKey}_{$minuteKey}";

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($branchId) {
            return $this->getDashboardStats($branchId);
        });
    }

    /**
     * 1. Total distinct members with active subscriptions.
     */
    protected function getActiveSubscribedMembersCount(?int $branchId = null): int
    {
        return DB::table('player_subscriptions as ps')
            ->where('ps.status', 'active')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($bq) use ($branchId) {
                    $bq->where('ps.branch_id', $branchId)
                       ->orWhereExists(function ($subQ) use ($branchId) {
                           $subQ->select(DB::raw(1))
                                ->from('subscription_plans as sp')
                                ->whereColumn('sp.id', 'ps.plan_id')
                                ->where('sp.branch_id', $branchId);
                       })
                       ->orWhereExists(function ($subQ) use ($branchId) {
                           $subQ->select(DB::raw(1))
                                ->from('members as m')
                                ->whereColumn('m.id', 'ps.member_id')
                                ->where('m.branch_id', $branchId);
                       });
                });
            })
            ->count(DB::raw('DISTINCT ps.member_id'));
    }

    /**
     * 2. Real-time checked-in members in non-session activities (is_session_based = false).
     */
    protected function getRealtimeTrainingPlayersCount(?int $branchId = null): int
    {
        return DB::table('attendances as att')
            ->where('att.attendable_type', 'member')
            ->where('att.status', 'checked_in')
            ->whereNull('att.check_out_at')
            ->when($branchId, fn($q) => $q->where('att.branch_id', $branchId))
            ->where(function ($query) {
                // 1. Member has active subscription with activity type having is_session_based = false (تدريب عام/خاص/دخول يومي)
                $query->whereExists(function ($subQ) {
                    $subQ->select(DB::raw(1))
                        ->from('player_subscriptions as ps')
                        ->join('player_subscription_items as psi', 'psi.player_subscription_id', '=', 'ps.id')
                        ->join('activities as act', 'act.id', '=', 'psi.activity_id')
                        ->leftJoin('activity_types as at', 'at.id', '=', 'act.activity_type_id')
                        ->whereColumn('ps.member_id', 'att.attendable_id')
                        ->where('ps.status', 'active')
                        ->where(function ($atQ) {
                            $atQ->where('at.is_session_based', false)
                                ->orWhereNull('act.activity_type_id');
                        });
                })
                // 2. Fallback: Member has active subscription for a plan not linked to session templates
                ->orWhereExists(function ($subQ2) {
                    $subQ2->select(DB::raw(1))
                        ->from('player_subscriptions as ps2')
                        ->join('subscription_plans as sp', 'sp.id', '=', 'ps2.plan_id')
                        ->whereColumn('ps2.member_id', 'att.attendable_id')
                        ->where('ps2.status', 'active')
                        ->whereNotExists(function ($sstQ) {
                            $sstQ->select(DB::raw(1))
                                 ->from('sport_session_templates as sst')
                                 ->whereColumn('sst.plan_id', 'sp.id');
                        });
                });
            })
            ->count(DB::raw('DISTINCT att.attendable_id'));
    }

    /**
     * 3. Currently active session templates with plan details and present players.
     */
    protected function getCurrentActiveSessionPlans(?int $branchId = null)
    {
        $currentDayOfWeek = now()->dayOfWeek; // 0 = Sunday, 1 = Monday ... 6 = Saturday
        $currentTime = now()->format('H:i:s');
        $todayDate = now()->toDateString();

        return DB::table('sport_session_templates as sst')
            ->join('subscription_plans as sp', 'sp.id', '=', 'sst.plan_id')
            ->leftJoin('attendances as att', function($join) use ($branchId) {
                $join->on('att.attendable_type', '=', DB::raw("'member'"))
                     ->where('att.status', '=', 'checked_in')
                     ->whereNull('att.check_out_at');
                if ($branchId) {
                    $join->where('att.branch_id', '=', $branchId);
                }
            })
            ->leftJoin('player_subscriptions as ps', function($join) {
                $join->on('ps.member_id', '=', 'att.attendable_id')
                     ->on('ps.plan_id', '=', 'sp.id')
                     ->where('ps.status', '=', 'active');
            })
            ->where('sst.is_active', true)
            ->where('sst.day_of_week', $currentDayOfWeek)
            ->whereTime('sst.start_time', '<=', $currentTime)
            ->whereTime('sst.end_time', '>=', $currentTime)
            ->whereNotExists(function ($subQ) use ($todayDate) {
                $subQ->select(DB::raw(1))
                     ->from('session_exceptions as se')
                     ->whereColumn('se.sport_session_template_id', 'sst.id')
                     ->where('se.date', $todayDate)
                     ->where('se.status', 'canceled');
            })
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($bq) use ($branchId) {
                    $bq->where('sst.branch_id', $branchId)
                       ->orWhere('sp.branch_id', $branchId);
                });
            })
            ->select(
                'sp.id as plan_id',
                'sp.name as plan_name',
                'sst.id as session_template_id',
                'sst.start_time',
                'sst.end_time',
                DB::raw('COUNT(DISTINCT CASE WHEN ps.id IS NOT NULL THEN att.attendable_id END) as present_players_count')
            )
            ->groupBy('sp.id', 'sp.name', 'sst.id', 'sst.start_time', 'sst.end_time')
            ->get()
            ->map(function($plan) {
                $plan->plan_name = json_decode($plan->plan_name, true) ?? $plan->plan_name;
                $plan->present_players_count = (int) $plan->present_players_count;
                return $plan;
            });
    }

    /**
     * 4. Count of subscriptions expiring soon (<= 3 sessions or <= 7 days).
     */
    protected function getExpiringSubscriptionsCount(?int $branchId = null): int
    {
        return DB::table('player_subscriptions as ps')
            ->where('ps.status', 'active')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($bq) use ($branchId) {
                    $bq->where('ps.branch_id', $branchId)
                       ->orWhereExists(function ($subQ) use ($branchId) {
                           $subQ->select(DB::raw(1))
                                ->from('subscription_plans as sp')
                                ->whereColumn('sp.id', 'ps.plan_id')
                                ->where('sp.branch_id', $branchId);
                       })
                       ->orWhereExists(function ($subQ) use ($branchId) {
                           $subQ->select(DB::raw(1))
                                ->from('members as m')
                                ->whereColumn('m.id', 'ps.member_id')
                                ->where('m.branch_id', $branchId);
                       });
                });
            })
            ->where(function($query) {
                $query->where(function($qDate) {
                    $qDate->whereNotNull('ps.end_date')
                          ->whereBetween('ps.end_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
                })
                ->orWhereExists(function($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('player_subscription_items as psi')
                        ->whereColumn('psi.player_subscription_id', 'ps.id')
                        ->where('psi.is_unlimited', false)
                        ->whereRaw('(psi.sessions_allocated - psi.sessions_consumed) <= 3')
                        ->whereRaw('(psi.sessions_allocated - psi.sessions_consumed) >= 0');
                });
            })
            ->count();
    }

    /**
     * 5. Count of free assigned lockers for members.
     */
    protected function getFreeAssignedLockersCount(?int $branchId = null): int
    {
        return DB::table('locker_reservations as lr')
            ->join('lockers as l', 'l.id', '=', 'lr.locker_id')
            ->where('lr.status', 'active')
            ->whereNotNull('lr.member_id')
            ->whereNull('lr.staff_id')
            ->where('lr.price', 0)
            ->when($branchId, fn($q) => $q->where('l.branch_id', $branchId))
            ->count();
    }
}

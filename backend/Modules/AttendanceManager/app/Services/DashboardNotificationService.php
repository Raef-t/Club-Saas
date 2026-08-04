<?php

namespace Modules\AttendanceManager\Services;

use Illuminate\Support\Facades\Cache;

class DashboardNotificationService
{
    /**
     * Notify system that stats for a branch (or all branches) have changed.
     *
     * @param int|null $branchId
     * @return void
     */
    public static function notifyBranchStatsChanged(?int $branchId = null): void
    {
        $branchKey = $branchId ? (string) $branchId : 'all';

        // Increment version counter in cache
        Cache::increment("dashboard_version_branch_{$branchKey}");
        
        // Forget global/all-branches version as well if branch-specific
        if ($branchId !== null) {
            Cache::increment("dashboard_version_branch_all");
            Cache::forget("dashboard_stats_cache_{$branchId}");
        }
        
        Cache::forget("dashboard_stats_cache_all");
    }

    /**
     * Get current version indicator for a given branch.
     *
     * @param int|null $branchId
     * @return int
     */
    public static function getBranchStatsVersion(?int $branchId = null): int
    {
        $branchKey = $branchId ? (string) $branchId : 'all';
        return (int) Cache::get("dashboard_version_branch_{$branchKey}", 1);
    }
}

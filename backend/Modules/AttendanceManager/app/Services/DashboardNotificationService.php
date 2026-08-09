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
        $newVersion = (int) (microtime(true) * 1000);

        if ($branchId !== null) {
            Cache::forever("dashboard_version_branch_{$branchId}", $newVersion);
        }

        Cache::forever("dashboard_version_branch_all", $newVersion);
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

        $branchVersion = (int) Cache::get("dashboard_version_branch_{$branchKey}", 1);
        $globalVersion = (int) Cache::get("dashboard_version_branch_all", 1);

        return max($branchVersion, $globalVersion);
    }
}

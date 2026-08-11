<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceConsumption extends Model
{
    use SoftDeletes;
    protected $table = 'attendance_consumptions';

    protected $fillable = [
        'attendance_id',
        'subscription_plan_id',
        'player_subscription_id',
    ];

    /**
     * Get the attendance associated with this consumption.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id')->withTrashed();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($consumption) {
            $branchId = $consumption->attendance?->branch_id;
            \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
        });

        static::deleted(function ($consumption) {
            $branchId = $consumption->attendance?->branch_id;
            \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
        });
    }
}

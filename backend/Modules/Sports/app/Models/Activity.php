<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'branch_id',
        'activity_type_id',
        'is_private_equipment',
        'is_active',
        'session_price',
        'exercises_count',
        'estimated_calories',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function ($activity) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($activity->branch_id);
            }
        });

        static::deleted(function ($activity) {
            // Cascade soft-delete
            \Modules\Sports\Models\StaffActivity::where('activity_id', $activity->id)->delete();
            
            if (class_exists(\Modules\Sports\Models\StaffCommissionRule::class)) {
                \Modules\Sports\Models\StaffCommissionRule::where('activity_id', $activity->id)->delete();
            }

            if (class_exists(\Modules\SubscriptionManager\Models\PlayerSubscriptionItem::class)) {
                \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::where('activity_id', $activity->id)->delete();
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($activity->branch_id);
            }
        });

        static::restored(function ($activity) {
            // Cascade restore
            \Modules\Sports\Models\StaffActivity::onlyTrashed()->where('activity_id', $activity->id)->restore();

            if (class_exists(\Modules\Sports\Models\StaffCommissionRule::class)) {
                \Modules\Sports\Models\StaffCommissionRule::onlyTrashed()->where('activity_id', $activity->id)->restore();
            }

            if (class_exists(\Modules\SubscriptionManager\Models\PlayerSubscriptionItem::class)) {
                \Modules\SubscriptionManager\Models\PlayerSubscriptionItem::onlyTrashed()->where('activity_id', $activity->id)->restore();
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($activity->branch_id);
            }
        });
    }

    /**
     * Get the activity type for the activity.
     */
    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    /**
     * Get the branch that owns the activity.
     */
    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class);
    }

    public function commissionRules()
    {
        return $this->hasMany(StaffCommissionRule::class, 'activity_id');
    }

}


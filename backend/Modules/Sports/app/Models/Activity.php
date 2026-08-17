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
        'is_private_equipment' => 'boolean',
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

    /**
     * Check if this activity has unlimited subscribers (derived primarily from its Activity Type).
     */
    public function hasUnlimitedSubscribers(): bool
    {
        // 1. Direct Activity Type configuration (Primary source of truth)
        if ($this->relationLoaded('activityType') && $this->activityType) {
            if ($this->activityType->has_unlimited_subscribers) {
                return true;
            }
        } elseif ($this->activity_type_id) {
            $type = $this->activityType;
            if ($type && $type->has_unlimited_subscribers) {
                return true;
            }
        }

        // 2. Private equipment flag (أجهزة خاص)
        if ($this->is_private_equipment) {
            return true;
        }

        // 3. Fallback name check for general equipment
        $name = trim((string) $this->name);
        if (in_array($name, ['أجهزة عام', 'أجهزة خاص', 'صالة الحديد والأجهزة'])) {
            return true;
        }

        return false;
    }

    /**
     * Backward-compatible alias for hasUnlimitedSubscribers
     */
    public function isEquipmentOrUnlimited(): bool
    {
        return $this->hasUnlimitedSubscribers();
    }

    /**
     * Check if any given activity ID represents an unlimited activity.
     *
     * @param int|array $activityIds
     * @return bool
     */
    public static function hasAnyEquipmentActivity(int|array $activityIds): bool
    {
        $ids = is_array($activityIds) ? array_filter($activityIds) : [$activityIds];
        if (empty($ids)) {
            return false;
        }

        $activities = static::with('activityType')->whereIn('id', $ids)->get();
        foreach ($activities as $activity) {
            if ($activity->hasUnlimitedSubscribers()) {
                return true;
            }
        }

        return false;
    }

    public static function hasAnyUnlimitedActivity(int|array $activityIds): bool
    {
        return static::hasAnyEquipmentActivity($activityIds);
    }

}


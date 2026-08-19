<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Traits\CascadeSoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;
    protected $table = 'attendances';

    protected $fillable = [
        'attendable_type',
        'attendable_id',
        'branch_id',
        // Staff member (receptionist) who registered this attendance
        'recorded_by_staff_id',
        'locker_id',
        'notes',
        'check_in_at',
        'check_out_at',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'check_in_at'  => 'datetime',
        'check_out_at' => 'datetime',
    ];

    /**
     * Get the parent attendable model (e.g., Staff, Member).
     */
    public function attendable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * Get the assigned locker for this attendance session.
     */
    public function locker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Locker::class, 'locker_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($attendance) {
            \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($attendance->branch_id);
        });

        static::deleted(function ($attendance) {
            \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($attendance->branch_id);
        });
    }

    /**
     * Get the consumptions associated with this attendance.
     */
    public function consumptions()
    {
        return $this->hasMany(AttendanceConsumption::class, 'attendance_id');
    }

    /**
     * Get the formatted duration in hours and minutes.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if ($this->duration_minutes === null) {
            return null;
        }

        $minutes = (int) $this->duration_minutes;
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours} ساعة";
        }
        if ($remainingMinutes > 0 || $hours === 0) {
            $parts[] = "{$remainingMinutes} دقيقة";
        }

        return implode(' و ', $parts);
    }
}

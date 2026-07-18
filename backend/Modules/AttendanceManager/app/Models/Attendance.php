<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'attendable_type',
        'attendable_id',
        'branch_id',
        // Staff member (receptionist) who registered this attendance
        'recorded_by_staff_id',
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
        return $this->morphTo();
    }

    /**
     * Get the consumptions associated with this attendance.
     */
    public function consumptions()
    {
        return $this->hasMany(AttendanceConsumption::class, 'attendance_id');
    }
}

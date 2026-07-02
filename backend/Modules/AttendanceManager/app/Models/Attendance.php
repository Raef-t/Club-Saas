<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'club_id',
        'attendable_type',
        'attendable_id',
        'branch_id',
        // Staff member (receptionist) who registered this attendance
        'recorded_by_staff_id',
        // Locker key assigned to this player during the visit
        'locker_id',
        // Name of the friend holding the key if transferred (not returned)
        'locker_holder_name',
        'check_in_at',
        'check_out_at',
        'status',
        'metadata',
    ];

    protected $casts = [
        'check_in_at'  => 'datetime',
        'check_out_at' => 'datetime',
        'metadata'     => 'array',
    ];

    /**
     * Get the parent attendable model (e.g., Staff, Member).
     */
    public function attendable(): MorphTo
    {
        return $this->morphTo();
    }
}

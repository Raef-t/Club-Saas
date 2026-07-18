<?php

namespace Modules\AttendanceManager\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceConsumption extends Model
{
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
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}

<?php

namespace Modules\Sports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StaffManager\Models\Staff;

class SessionException extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'session_exceptions';

    protected $fillable = [
        'sport_session_template_id',
        'coach_id',
        'date',
        'status',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function template()
    {
        return $this->belongsTo(SportSessionTemplate::class, 'sport_session_template_id');
    }

    public function coach()
    {
        // Assuming there is a Staff model in Modules\Core\Models\Staff or somewhere else
        return $this->belongsTo(Staff::class, 'coach_id'); // Fallback: try checking if it exists
    }

    protected static function booted()
    {
        static::saved(function ($exception) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged();
            }
        });

        static::deleted(function ($exception) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged();
            }
        });
    }
}

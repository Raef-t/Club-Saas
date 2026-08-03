<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StaffManager\Models\Staff;

class LockerReservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'locker_id',
        'member_id',
        'staff_id',
        'invoice_id',
        'start_date',
        'end_date',
        'price',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
    ];

    // Assuming member relation
    public function member()
    {
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class);
    }

    // Assuming staff relation
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged();
            }
        });

        static::deleted(function () {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged();
            }
        });
    }
}

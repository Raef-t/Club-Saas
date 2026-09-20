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
        'reason',
        'is_refund',
        'refund_amount',
        'refund_safe_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'is_refund' => 'boolean',
        'refund_amount' => 'decimal:2',
        'refund_safe_id' => 'integer',
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function locker()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Locker::class, 'locker_id');
    }

    public function refundSafe()
    {
        return $this->belongsTo(\Modules\Accounting\Models\AccSafe::class, 'refund_safe_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($reservation) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $branchId = $reservation->locker?->branch_id;
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
            }
        });

        static::deleted(function ($reservation) {
            if (class_exists(\Modules\ClubManager\Models\Locker::class)) {
                if ($reservation->status === 'active') {
                    $locker = \Modules\ClubManager\Models\Locker::find($reservation->locker_id);
                    if ($locker) {
                        $locker->update(['status' => 'available']);
                    }
                }
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $branchId = $reservation->locker?->branch_id;
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
            }
        });
    }
}

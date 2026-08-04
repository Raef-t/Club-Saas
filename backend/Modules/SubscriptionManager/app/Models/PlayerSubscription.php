<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\CascadeSoftDeletes;

class PlayerSubscription extends Model
{
    use \Modules\Core\Traits\HasCreatedBy, SoftDeletes, CascadeSoftDeletes;

    protected array $cascadeDeletes = [
        'items',
        'freezes',
        'invoices',
        'attendanceConsumptions',
    ];

    protected $fillable = [
        'member_id',
        'plan_id',
        'months_count',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'notes',
        'offer_id',
    ];

    protected $casts = [
        'months_count' => 'integer',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'paid_amount' => 'decimal:2',
        'status' => \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::class,
    ];


    public function member()
    {
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class, 'member_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id')->withTrashed();
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    public function freezes()
    {
        return $this->hasMany(SubscriptionFreeze::class);
    }

    public function items()
    {
        return $this->hasMany(PlayerSubscriptionItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'player_subscription_id');
    }

    public function attendanceConsumptions()
    {
        return $this->hasMany(\Modules\AttendanceManager\Models\AttendanceConsumption::class, 'player_subscription_id');
    }


    public function getIsFullyPaidAttribute()
    {
        return (float) $this->paid_amount >= (float) $this->total_amount;
    }
    
    protected $appends = ['is_fully_paid'];

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

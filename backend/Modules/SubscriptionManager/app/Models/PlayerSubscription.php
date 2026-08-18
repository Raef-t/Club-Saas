<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerSubscription extends Model
{
    use \Modules\Core\Traits\HasCreatedBy, SoftDeletes;

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
        'reason',
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
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class, 'member_id')->withTrashed();
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id')->withTrashed();
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id')->withTrashed();
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

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Invoice::class, 'player_subscription_id', 'invoice_id');
    }

    public function attendanceConsumptions()
    {
        return $this->hasMany(\Modules\AttendanceManager\Models\AttendanceConsumption::class, 'player_subscription_id');
    }

    public function revenueSplit()
    {
        return $this->hasOne(SubscriptionRevenueSplit::class, 'player_subscription_id');
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
        static::saved(function ($subscription) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $branchId = $subscription->branch_id ?? $subscription->member?->branch_id;
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
            }
        });

        static::deleted(function ($subscription) {
            if ($subscription->isForceDeleting()) {
                return;
            }

            // Soft-delete items
            $subscription->items()->delete();

            // Soft-delete freezes
            $subscription->freezes()->delete();

            // Soft-delete invoices (which cascades to payments)
            $subscription->invoices()->get()->each(function ($invoice) {
                $invoice->delete();
            });

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $branchId = $subscription->branch_id ?? $subscription->member?->branch_id;
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
            }
        });

        static::restored(function ($subscription) {
            // Restore items
            $subscription->items()->onlyTrashed()->restore();

            // Restore freezes
            $subscription->freezes()->onlyTrashed()->restore();

            // Restore invoices (which cascades to payments)
            $subscription->invoices()->onlyTrashed()->get()->each(function ($invoice) {
                $invoice->restore();
            });

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $branchId = $subscription->branch_id ?? $subscription->member?->branch_id;
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
            }
        });
    }
}

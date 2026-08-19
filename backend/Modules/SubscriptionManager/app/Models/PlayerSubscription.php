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
     * Scope to filter subscriptions active on a specific date.
     */
    public function scopeActiveOnDate($query, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $query->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE->value)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    /**
     * Scope to exclude subscriptions that are frozen on a specific date.
     */
    public function scopeNotFrozenOnDate($query, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $query->whereNotExists(function ($freezeQ) use ($date) {
            $freezeQ->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('subscription_freezes as sf')
                ->whereColumn('sf.player_subscription_id', 'player_subscriptions.id')
                ->whereNull('sf.deleted_at')
                ->whereDate('sf.freeze_start_date', '<=', $date)
                ->whereDate('sf.freeze_end_date', '>=', $date);
        });
    }

    /**
     * Scope to exclude subscriptions whose plan is suspended on a specific date.
     */
    public function scopePlanNotSuspendedOnDate($query, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $query->whereNotExists(function ($suspQ) use ($date) {
            $suspQ->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('subscription_plan_suspensions as sps')
                ->whereColumn('sps.plan_id', 'player_subscriptions.plan_id')
                ->whereNull('sps.deleted_at')
                ->where(function ($subQ) use ($date) {
                    $subQ->where('sps.status', 'active')
                         ->orWhere(function ($dateQ) use ($date) {
                             $dateQ->whereDate('sps.suspend_start_date', '<=', $date)
                                   ->whereDate('sps.suspend_end_date', '>=', $date);
                         });
                });
        });
    }

    /**
     * Scope to filter subscriptions that have scheduled sessions on a specific date (or open plans without templates).
     */
    public function scopeHasSessionOnDate($query, ?string $date = null)
    {
        $checkDate = $date ? \Carbon\Carbon::parse($date) : now();
        $dateString = $checkDate->toDateString();
        $dayOfWeek = (int) $checkDate->dayOfWeek;

        return $query->where(function ($sessionQ) use ($dayOfWeek, $dateString) {
            // Case 1: Plan has NO session templates defined (open gym/equipment/daily entry)
            $sessionQ->whereNotExists(function ($noTmplQ) {
                $noTmplQ->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('sport_session_templates as sst_all')
                    ->whereColumn('sst_all.plan_id', 'player_subscriptions.plan_id')
                    ->where('sst_all.is_active', true)
                    ->whereNull('sst_all.deleted_at');
            })
            // Case 2: Plan HAS session templates, and has at least one template matching today's day_of_week and not cancelled
            ->orWhereExists(function ($hasTmplQ) use ($dayOfWeek, $dateString) {
                $hasTmplQ->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('sport_session_templates as sst_today')
                    ->whereColumn('sst_today.plan_id', 'player_subscriptions.plan_id')
                    ->where('sst_today.is_active', true)
                    ->where('sst_today.day_of_week', $dayOfWeek)
                    ->whereNull('sst_today.deleted_at')
                    ->whereNotExists(function ($excQ) use ($dateString) {
                        $excQ->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('session_exceptions as se')
                            ->whereColumn('se.sport_session_template_id', 'sst_today.id')
                            ->whereDate('se.date', $dateString)
                            ->whereIn('se.status', ['cancelled', 'canceled'])
                            ->whereNull('se.deleted_at');
                    });
            });
        });
    }

    /**
     * Scope to filter subscriptions with remaining sessions or unlimited items.
     */
    public function scopeHasRemainingSessions($query)
    {
        return $query->where(function ($q) {
            $q->whereDoesntHave('items')
              ->orWhereHas('items', function ($itemQ) {
                  $itemQ->where(function ($subItemQ) {
                      $subItemQ->where('is_unlimited', true)
                               ->orWhereRaw('sessions_allocated > sessions_consumed');
                  });
              });
        });
    }

    /**
     * Combined scope to filter subscriptions available for attendance check-in on a given date.
     */
    public function scopeAvailableForAttendanceOnDate($query, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        return $query->activeOnDate($date)
            ->notFrozenOnDate($date)
            ->planNotSuspendedOnDate($date)
            ->hasSessionOnDate($date)
            ->hasRemainingSessions();
    }

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

            // Decrement plan subscribers count
            if ($subscription->plan_id) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::find($subscription->plan_id);
                if ($plan) {
                    app(\Modules\SubscriptionManager\Services\SubscriptionService::class)->decrementPlanSubscribers($plan);
                }
            }

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

            // Increment plan subscribers count
            if ($subscription->plan_id) {
                $plan = \Modules\SubscriptionManager\Models\SubscriptionPlan::find($subscription->plan_id);
                if ($plan) {
                    app(\Modules\SubscriptionManager\Services\SubscriptionService::class)->incrementPlanSubscribers($plan);
                }
            }

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                $branchId = $subscription->branch_id ?? $subscription->member?->branch_id;
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($branchId);
            }
        });
    }
}

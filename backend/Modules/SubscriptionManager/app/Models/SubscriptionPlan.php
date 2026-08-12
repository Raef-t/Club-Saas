<?php

namespace Modules\SubscriptionManager\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Modules\SubscriptionManager\Enums\SubscriptionPlanStatus;

class SubscriptionPlan extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'branch_id',
        'subscription_number',
        'name',
        'session_count',
        'sessions_per_week',
        'base_price',
        'status',
        'max_subscribers',
        'current_subscribers',
        'gender_restriction',
    ];

    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->subscription_number)) {
                $plan->subscription_number = self::generateUniqueSubscriptionNumber();
            }
        });

        static::deleted(function ($plan) {
            if ($plan->isForceDeleting()) {
                return;
            }

            $plan->planActivities()->delete();

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                $plan->sessionTemplates()->delete();
            }

            \Modules\SubscriptionManager\Models\PlayerSubscription::where('plan_id', $plan->id)->delete();
        });

        static::restored(function ($plan) {
            $plan->planActivities()->onlyTrashed()->restore();

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                $plan->sessionTemplates()->onlyTrashed()->restore();
            }

            \Modules\SubscriptionManager\Models\PlayerSubscription::onlyTrashed()->where('plan_id', $plan->id)->restore();
        });
    }

    public static function generateUniqueSubscriptionNumber()
    {
        do {
            $number = random_int(10000000, 99999999);
        } while (self::where('subscription_number', $number)->exists());

        return (string) $number;
    }

    protected $casts = [
        'status' => SubscriptionPlanStatus::class,
        'base_price' => 'decimal:2',
        'max_subscribers' => 'integer',
        'current_subscribers' => 'integer',
        'sessions_per_week' => 'integer',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_subscribers')
              ->orWhere('max_subscribers', 0)
              ->orWhereColumn('current_subscribers', '<', 'max_subscribers');
        });
    }

    public function planActivities()
    {
        return $this->hasMany(SubscriptionPlanActivity::class, 'plan_id');
    }

    public function sessionTemplates()
    {
        return $this->hasMany(\Modules\Sports\Models\SportSessionTemplate::class, 'plan_id');
    }

    /**
     * Get the branch that owns the subscription plan.
     */
    public function branch()
    {
        return $this->belongsTo(\Modules\ClubManager\Models\Branch::class);
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_subscription_plan');
    }

    public function playerSubscriptions()
    {
        return $this->hasMany(PlayerSubscription::class, 'plan_id');
    }

    public function suspensions()
    {
        return $this->hasMany(SubscriptionPlanSuspension::class, 'plan_id');
    }
}

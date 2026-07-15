<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{


    protected $fillable = [
        'branch_id',
        'subscription_number',
        'name',
        'type',
        'start_date',
        'end_date',
        'duration_days',
        'session_count',
        'sessions_per_week',
        'base_price',
        'is_active',
        'max_subscribers',
        'current_subscribers',
    ];

    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->subscription_number)) {
                $plan->subscription_number = self::generateUniqueSubscriptionNumber();
            }
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
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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
        return $query->where('is_active', true);
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
}

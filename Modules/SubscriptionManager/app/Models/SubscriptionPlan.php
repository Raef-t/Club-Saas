<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_number',
        'name',
        'type',
        'duration_days',
        'session_count',
        'max_freeze_count',
        'max_freeze_days',
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
        'name' => 'json',
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'max_freeze_count' => 'integer',
        'max_freeze_days' => 'integer',
        'max_subscribers' => 'integer',
        'current_subscribers' => 'integer',
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
            $q->where('max_subscribers', 0)
              ->orWhereColumn('current_subscribers', '<', 'max_subscribers');
        });
    }

    public function planActivities()
    {
        return $this->hasMany(SubscriptionPlanActivity::class, 'plan_id');
    }
}

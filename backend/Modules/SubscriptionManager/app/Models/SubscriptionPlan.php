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
        'club_commission_percentage',
        'coach_commission_percentage',
        'reason',
    ];

    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->subscription_number)) {
                $plan->subscription_number = self::generateUniqueSubscriptionNumber();
            }

            if ($plan->club_commission_percentage === null && $plan->branch_id) {
                $branchSetting = \Modules\ClubManager\Models\BranchSetting::where('branch_id', $plan->branch_id)->first();
                $plan->club_commission_percentage = $branchSetting ? (float) ($branchSetting->private_subscription_commission ?? 0) : 0.00;
            }

            if ($plan->coach_commission_percentage === null) {
                $clubCommission = (float) ($plan->club_commission_percentage ?? 0);
                $plan->coach_commission_percentage = max(0, 100.00 - $clubCommission);
            }
        });

        static::saving(function ($plan) {
            if ($plan->relationLoaded('planActivities') && $plan->hasEquipmentActivity()) {
                $plan->max_subscribers = 0;
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

            \Modules\SubscriptionManager\Models\PlayerSubscription::where('plan_id', $plan->id)->get()->each(function ($subscription) {
                $subscription->delete();
            });
        });

        static::restored(function ($plan) {
            $plan->planActivities()->onlyTrashed()->restore();

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                $plan->sessionTemplates()->onlyTrashed()->restore();
            }

            \Modules\SubscriptionManager\Models\PlayerSubscription::onlyTrashed()->where('plan_id', $plan->id)->get()->each(function ($subscription) {
                $subscription->restore();
            });
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
        'club_commission_percentage' => 'decimal:2',
        'coach_commission_percentage' => 'decimal:2',
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

    /**
     * Determine if this subscription plan includes an equipment or unlimited activity (e.g. أجهزة عام / أجهزة خاص).
     */
    public function hasEquipmentActivity(): bool
    {
        $activities = $this->relationLoaded('planActivities')
            ? $this->planActivities
            : $this->planActivities()->with('staffActivity.activity.activityType')->get();

        foreach ($activities as $planActivity) {
            $activity = $planActivity->activity ?? ($planActivity->staffActivity ? $planActivity->staffActivity->activity : null);
            if ($activity && method_exists($activity, 'isEquipmentOrUnlimited') && $activity->isEquipmentOrUnlimited()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if plan allows unlimited subscribers.
     */
    public function getIsUnlimitedSubscribersAttribute(): bool
    {
        return $this->max_subscribers === 0 || $this->max_subscribers === null || $this->hasEquipmentActivity();
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

    public function activeSuspension()
    {
        return $this->hasOne(SubscriptionPlanSuspension::class, 'plan_id')
                    ->whereIn('status', ['active', 'scheduled'])
                    ->latestOfMany();
    }
}

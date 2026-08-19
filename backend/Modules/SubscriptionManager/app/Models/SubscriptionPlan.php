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
        'reason',
    ];

    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->subscription_number)) {
                $plan->subscription_number = self::generateUniqueSubscriptionNumber();
            }
        });

        static::saving(function ($plan) {
            if ($plan->relationLoaded('planActivities') && $plan->hasEquipmentActivity()) {
                $plan->max_subscribers = 0;
            }
        });

        static::saved(function ($plan) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($plan->branch_id);
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

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($plan->branch_id);
            }
        });

        static::restored(function ($plan) {
            $plan->planActivities()->onlyTrashed()->restore();

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                $plan->sessionTemplates()->onlyTrashed()->restore();
            }

            \Modules\SubscriptionManager\Models\PlayerSubscription::onlyTrashed()->where('plan_id', $plan->id)->get()->each(function ($subscription) {
                $subscription->restore();
            });

            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($plan->branch_id);
            }
        });

        static::forceDeleted(function ($plan) {
            if (class_exists(\Modules\AttendanceManager\Services\DashboardNotificationService::class)) {
                \Modules\AttendanceManager\Services\DashboardNotificationService::notifyBranchStatsChanged($plan->branch_id);
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
     * Determine if this subscription plan is specifically a private equipment plan (أجهزة خاص).
     */
    public function isPrivateEquipmentPlan(): bool
    {
        $activities = $this->relationLoaded('planActivities')
            ? $this->planActivities
            : $this->planActivities()->with('staffActivity.activity.activityType')->get();

        foreach ($activities as $planActivity) {
            $activity = $planActivity->activity ?? ($planActivity->staffActivity ? $planActivity->staffActivity->activity : null);
            if ($activity) {
                if (!empty($activity->is_private_equipment)) {
                    return true;
                }
                $name = trim((string) $activity->name);
                $lowerName = strtolower($name);
                if (in_array($name, ['أجهزة خاص', 'اجهزة خاص', 'تدريب خاص', 'خاص أجهزة', 'خاص اجهزة'])) {
                    return true;
                }
                if (str_contains($name, 'خاص') || str_contains($lowerName, 'private')) {
                    return true;
                }
            }
        }

        $planName = trim((string) $this->name);
        $lowerPlanName = strtolower($planName);
        if (str_contains($planName, 'خاص') || str_contains($lowerPlanName, 'private')) {
            return true;
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

    /**
     * Get the dynamically calculated count of active subscribers from player_subscriptions.
     */
    public function getCurrentSubscribersCount(): int
    {
        if (array_key_exists('active_subscribers_count', $this->attributes)) {
            return (int) $this->attributes['active_subscribers_count'];
        }

        if ($this->relationLoaded('playerSubscriptions')) {
            return $this->playerSubscriptions
                ->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE)
                ->count();
        }

        if ($this->exists) {
            return $this->playerSubscriptions()
                ->where('status', \Modules\SubscriptionManager\Enums\PlayerSubscriptionStatus::ACTIVE)
                ->count();
        }

        return (int) ($this->attributes['current_subscribers'] ?? 0);
    }
}

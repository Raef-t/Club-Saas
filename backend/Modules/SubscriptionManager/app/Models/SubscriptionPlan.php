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
        'coach_price',
        'branch_price',
        'currency',
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

        static::deleted(function ($plan) {
            if ($plan->isForceDeleting()) {
                return;
            }

            $plan->planActivities()->delete();

            if (class_exists(\Modules\Sports\Models\SportSessionTemplate::class)) {
                $plan->sessionTemplates()->delete();
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
        'coach_price' => 'decimal:2',
        'branch_price' => 'decimal:2',
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

    public function scopeActiveActivities($query)
    {
        return $query->whereDoesntHave('planActivities.staffActivity.activity', function ($q) {
            $q->where('is_active', false);
        });
    }

    public function scopeActiveCoaches($query)
    {
        return $query->whereDoesntHave('planActivities.staffActivity.staff', function ($q) {
            $q->where('is_active', false)
              ->orWhere('work_status', '!=', 'active');
        });
    }

    public function scopeNotSuspended($query)
    {
        return $query->whereDoesntHave('suspensions', function ($q) {
            $q->whereIn('status', ['active', 'scheduled']);
        });
    }

    public function scopeForActivityType($query, $activityType)
    {
        if ($activityType === null || $activityType === '' || $activityType === 'all' || $activityType === 'الكل') {
            return $query;
        }

        if (is_array($activityType)) {
            return $query->whereHas('planActivities.staffActivity.activity', function ($subQuery) use ($activityType) {
                $subQuery->whereIn('activity_type_id', $activityType);
            });
        }

        if (is_numeric($activityType)) {
            return $query->whereHas('planActivities.staffActivity.activity', function ($subQuery) use ($activityType) {
                $subQuery->where('activity_type_id', (int) $activityType);
            });
        }

        if (is_string($activityType) && str_contains($activityType, ',')) {
            $ids = array_filter(array_map('trim', explode(',', $activityType)), 'is_numeric');
            if (!empty($ids)) {
                return $query->whereHas('planActivities.staffActivity.activity', function ($subQuery) use ($ids) {
                    $subQuery->whereIn('activity_type_id', $ids);
                });
            }
        }

        $typeStr = mb_strtolower(trim((string) $activityType));

        // 1. Private / الخاص
        $privateKeywords = ['الخاص', 'خاص', 'تدريب خاص', 'تدريب_خاص', 'private', 'private_training', 'private_equipment'];
        if (in_array($typeStr, $privateKeywords, true)) {
            return $query->where(function ($planQ) {
                $planQ->whereHas('planActivities.staffActivity.activity', function ($subQuery) {
                    $subQuery->where(function ($aq) {
                        $aq->where(function ($privQ) {
                            $privQ->where('is_private_equipment', true)
                                  ->orWhere('name', 'like', '%خاص%')
                                  ->orWhere('name', 'like', '%private%')
                                  ->orWhereHas('activityType', function ($tq) {
                                      $tq->where('name', 'like', '%خاص%')
                                         ->orWhere('name', 'like', '%private%')
                                         ->orWhere(function ($eq) {
                                             $eq->where('is_private_equipment', true)
                                                ->where('name', 'not like', '%عام%')
                                                ->where('name', 'not like', '%general%')
                                                ->where('is_session_based', false);
                                         });
                                  });
                        })
                        ->where('name', 'not like', '%عام%')
                        ->whereDoesntHave('activityType', function ($gtq) {
                            $gtq->where('name', 'like', '%عام%');
                        });
                    });
                })
                ->orWhere(function ($pq) {
                    $pq->where(function ($npq) {
                        $npq->where('subscription_plans.name', 'like', '%خاص%')
                            ->orWhere('subscription_plans.name', 'like', '%private%');
                    })
                    ->where('subscription_plans.name', 'not like', '%عام%');
                });
            });
        }

        // 2. Group Session / الحصة الجماعية
        $groupKeywords = ['الحصة الجماعية', 'الحصة_الجماعية', 'حصة جماعية', 'حصة_جماعية', 'حصة جماعيه', 'جماعي', 'جماعية', 'group', 'group_session', 'group_sessions', 'session', 'sessions'];
        if (in_array($typeStr, $groupKeywords, true)) {
            return $query->whereHas('planActivities.staffActivity.activity', function ($subQuery) {
                $subQuery->where(function ($aq) {
                    $aq->whereHas('activityType', function ($tq) {
                        $tq->where('is_session_based', true)
                           ->orWhere('name', 'like', '%جماع%')
                           ->orWhere('name', 'like', '%حصة%')
                           ->orWhere('name', 'like', '%session%')
                           ->orWhere('name', 'like', '%group%');
                    })
                    ->orWhere('name', 'like', '%جماع%')
                    ->orWhere('name', 'like', '%حصة%')
                    ->orWhere('name', 'like', '%session%');
                });
            });
        }

        // 3. Public / General / العام
        $generalKeywords = ['العام', 'عام', 'تدريب عام', 'تدريب_عام', 'general', 'public', 'general_training'];
        if (in_array($typeStr, $generalKeywords, true)) {
            return $query->whereHas('planActivities.staffActivity.activity', function ($subQuery) {
                $subQuery->where(function ($aq) {
                    $aq->whereHas('activityType', function ($tq) {
                        $tq->where('name', 'like', '%عام%')
                           ->orWhere('name', 'like', '%general%')
                           ->orWhere('name', 'like', '%public%')
                           ->orWhere(function ($gq) {
                               $gq->where('is_session_based', false)
                                  ->where(function ($pq) {
                                      $pq->where('is_private_equipment', false)->orWhereNull('is_private_equipment');
                                  });
                           });
                    })
                    ->where(function ($pq) {
                        $pq->where('is_private_equipment', false)->orWhereNull('is_private_equipment');
                    })
                    ->where('name', 'not like', '%خاص%');
                });
            });
        }

        // 4. Custom Activity Type Name or ID
        $stripped = preg_replace('/^ال/u', '', $typeStr);
        return $query->whereHas('planActivities.staffActivity.activity', function ($subQuery) use ($typeStr, $stripped) {
            $subQuery->whereHas('activityType', function ($typeQuery) use ($typeStr, $stripped) {
                $typeQuery->where('name', 'like', "%{$typeStr}%")
                          ->orWhere('name', 'like', "%{$stripped}%")
                          ->orWhere('id', $typeStr);
            });
        });
    }

    public function scopeForActivity($query, $activity)
    {
        if ($activity === null || $activity === '') {
            return $query;
        }

        return $query->whereHas('planActivities.staffActivity', function ($subQuery) use ($activity) {
            if (is_array($activity)) {
                $subQuery->whereIn('activity_id', $activity);
            } elseif (is_numeric($activity)) {
                $subQuery->where('activity_id', (int) $activity);
            } elseif (is_string($activity) && str_contains($activity, ',')) {
                $ids = array_filter(array_map('trim', explode(',', $activity)), 'is_numeric');
                $subQuery->whereIn('activity_id', $ids);
            }
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
     * Determine if this subscription plan represents a group session / session-based plan (حصة جماعية / is_session_based).
     */
    public function isGroupSessionPlan(): bool
    {
        $activities = $this->relationLoaded('planActivities')
            ? $this->planActivities
            : $this->planActivities()->with('staffActivity.activity.activityType')->get();

        foreach ($activities as $planActivity) {
            $staffActivity = $planActivity->staffActivity;
            $activity = $staffActivity ? $staffActivity->activity : ($planActivity->activity ?? null);
            if ($activity) {
                if (method_exists($activity, 'isGroupSession') && $activity->isGroupSession()) {
                    return true;
                }
                $type = $activity->relationLoaded('activityType') ? $activity->activityType : $activity->activityType()->first();
                if ($type && $type->is_session_based) {
                    return true;
                }
            }
        }

        if ($this->exists) {
            $hasSessionBased = $this->planActivities()
                ->whereHas('staffActivity.activity.activityType', function ($q) {
                    $q->where('is_session_based', true);
                })
                ->exists();

            if ($hasSessionBased) {
                return true;
            }
        }

        $planName = trim((string) $this->name);
        $groupKeywords = ['حصة جماعية', 'حصة_جماعية', 'حصة جماعيه', 'جماعي', 'جماعية', 'group', 'session'];
        foreach ($groupKeywords as $kw) {
            if (str_contains($planName, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alias for isGroupSessionPlan.
     */
    public function isSessionBasedPlan(): bool
    {
        return $this->isGroupSessionPlan();
    }

    /**
     * Determine if plan allows unlimited subscribers.
     */
    public function getIsUnlimitedSubscribersAttribute(): bool
    {
        return $this->max_subscribers === 0 || $this->max_subscribers === null || $this->hasEquipmentActivity();
    }

    public function getCurrencyTypeAttribute(): string
    {
        return $this->currency ?? 'SYP';
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

    public function isCurrentlySuspended(): bool
    {
        if ($this->relationLoaded('activeSuspension')) {
            return $this->activeSuspension !== null;
        }

        return $this->suspensions()->whereIn('status', ['active', 'scheduled'])->exists();
    }

    public function hasInactiveActivities(): bool
    {
        $activities = $this->relationLoaded('planActivities')
            ? $this->planActivities
            : $this->planActivities()->with('staffActivity.activity')->get();

        foreach ($activities as $planActivity) {
            $activity = $planActivity->activity ?? ($planActivity->staffActivity ? $planActivity->staffActivity->activity : null);
            if ($activity && !$activity->is_active) {
                return true;
            }
        }

        return false;
    }

    public function hasInactiveCoaches(): bool
    {
        $activities = $this->relationLoaded('planActivities')
            ? $this->planActivities
            : $this->planActivities()->with('staffActivity.staff')->get();

        foreach ($activities as $planActivity) {
            $coach = $planActivity->staffActivity ? $planActivity->staffActivity->staff : null;
            if ($coach && (!$coach->is_active || $coach->work_status !== 'active')) {
                return true;
            }
        }

        return false;
    }
}

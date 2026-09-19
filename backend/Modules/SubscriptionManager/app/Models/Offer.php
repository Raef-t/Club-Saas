<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Modules\ClubManager\Models\Branch;

class Offer extends Model
{
    use SoftDeletes;


    public const TYPE_BUNDLE = 'bundle';
    public const TYPE_SINGLE_CHOICE = 'single_choice';

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'offer_type',
        'price',
        'duration_days',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function getDurationMonthsAttribute(): ?float
    {
        return $this->duration_days ? round($this->duration_days / 30, 1) : null;
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (empty($this->duration_days)) {
            return null;
        }

        $days = (int) $this->duration_days;
        if ($days === 30) {
            return 'شهر واحد (30 يوم)';
        } elseif ($days === 60) {
            return 'شهران (60 يوم)';
        } elseif ($days === 90) {
            return '3 أشهر (90 يوم)';
        } elseif ($days === 180) {
            return '6 أشهر (180 يوم)';
        } elseif ($days === 365) {
            return 'سنة كاملة (365 يوم)';
        } elseif ($days % 30 === 0) {
            $months = $days / 30;
            return "{$months} أشهر ({$days} يوم)";
        } elseif ($days === 45) {
            return 'شهر ونصف (45 يوم)';
        } else {
            $months = floor($days / 30);
            $remDays = $days % 30;
            if ($months > 0 && $remDays > 0) {
                return "{$months} شهر و {$remDays} يوم ({$days} يوم)";
            }
            return "{$days} يوم";
        }
    }

    public function isBundle(): bool
    {
        return empty($this->offer_type) || $this->offer_type === self::TYPE_BUNDLE;
    }

    public function isSingleChoice(): bool
    {
        return $this->offer_type === self::TYPE_SINGLE_CHOICE;
    }

    public function isDateValid(): bool
    {
        $today = now()->startOfDay();
        if ($this->start_date && $this->start_date->startOfDay()->isAfter($today)) {
            return false;
        }
        if ($this->end_date && $this->end_date->endOfDay()->isBefore($today)) {
            return false;
        }
        return true;
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function plans()
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'offer_subscription_plan');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'offer_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(PlayerSubscription::class, 'offer_id');
    }

    protected static function booted(): void
    {
        static::deleted(function ($offer) {
            if ($offer->isForceDeleting()) {
                return;
            }

            $offer->subscriptions()->get()->each(function ($subscription) {
                $subscription->delete();
            });
        });

        static::restored(function ($offer) {
            $offer->subscriptions()->onlyTrashed()->get()->each(function ($subscription) {
                $subscription->restore();
            });
        });
    }
}

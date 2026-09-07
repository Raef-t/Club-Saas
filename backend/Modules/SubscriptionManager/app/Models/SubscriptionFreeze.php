<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionFreeze extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'player_subscription_id',
        'subscription_plan_suspension_id',
        'freeze_start_date',
        'freeze_end_date',
        'actual_end_date',
        'reason',
    ];

    protected $casts = [
        'freeze_start_date' => 'date',
        'freeze_end_date' => 'date',
        'actual_end_date' => 'date',
    ];

    protected $appends = [
        'freeze_days',
    ];

    public function getFreezeDaysAttribute(): int
    {
        if (!$this->freeze_start_date) {
            return 1;
        }

        $startDate = \Carbon\Carbon::parse($this->freeze_start_date)->startOfDay();
        $endDate = $this->actual_end_date
            ? \Carbon\Carbon::parse($this->actual_end_date)->startOfDay()
            : ($this->freeze_end_date
                ? \Carbon\Carbon::parse($this->freeze_end_date)->startOfDay()
                : \Carbon\Carbon::today());

        $days = (int) $startDate->diffInDays($endDate);
        return max(1, $days);
    }

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }

    public function suspension()
    {
        return $this->belongsTo(SubscriptionPlanSuspension::class, 'subscription_plan_suspension_id');
    }
}

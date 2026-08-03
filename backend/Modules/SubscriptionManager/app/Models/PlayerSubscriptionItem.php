<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerSubscriptionItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'player_subscription_id',
        'activity_id',
        'coach_id',
        'sessions_allocated',
        'sessions_consumed',
        'is_unlimited',
    ];

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }

    public function activity()
    {
        return $this->belongsTo(\Modules\Sports\Models\Activity::class, 'activity_id');
    }

    public function coach()
    {
        return $this->belongsTo(\Modules\StaffManager\Models\Staff::class, 'coach_id');
    }
}

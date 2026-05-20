<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
class PlayerSubscriptionItem extends Model
{
    protected $fillable = [
        'player_subscription_id',
        'activity_id',
        'sessions_allocated',
        'sessions_consumed',
        'is_unlimited',
    ];

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }
}

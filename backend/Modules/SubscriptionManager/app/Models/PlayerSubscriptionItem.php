<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerSubscriptionItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'player_subscription_id',
        'sessions_allocated',
        'sessions_consumed',
        'is_unlimited',
    ];

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id')->withTrashed();
    }
}

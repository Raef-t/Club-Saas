<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
class SubscriptionFreeze extends Model
{
    protected $fillable = [
        'player_subscription_id',
        'freeze_start_date',
        'freeze_end_date',
        'reason',
    ];

    protected $casts = [
        'freeze_start_date' => 'date',
        'freeze_end_date' => 'date',
    ];

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }
}

<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionFreeze extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'player_subscription_id',
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

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }
}

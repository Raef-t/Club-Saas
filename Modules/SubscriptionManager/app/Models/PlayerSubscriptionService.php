<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerSubscriptionService extends Model
{
    protected $table = 'player_subscription_services';

    protected $fillable = [
        'player_subscription_id',
        'extra_service_id',
        'price_charged',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'price_charged' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }

    public function extraService()
    {
        return $this->belongsTo(ExtraService::class, 'extra_service_id');
    }
}

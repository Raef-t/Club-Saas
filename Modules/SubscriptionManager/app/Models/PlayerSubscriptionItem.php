<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class PlayerSubscriptionItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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

    public function activity()
    {
        return $this->belongsTo(\Modules\Sports\Models\Activity::class);
    }
}

<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;

class SubscriptionFreeze extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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

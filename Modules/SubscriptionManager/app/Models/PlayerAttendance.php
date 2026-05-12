<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\BelongsToTenant;
use Modules\MemberManager\Models\Member;

class PlayerAttendance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'member_id',
        'player_subscription_id',
        'activity_id',
        'check_in',
        'check_out',
        'duration_minutes',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }
}

<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
class PlayerAttendance extends Model
{
    protected $fillable = [
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

    public ?\Modules\Core\DTOs\MemberDTO $member = null;

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }
}

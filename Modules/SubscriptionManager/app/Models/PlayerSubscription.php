<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\BelongsToTenant;
use Modules\MemberManager\Models\Member;

class PlayerSubscription extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'member_id',
        'coach_id',
        'plan_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'remaining_sessions',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function freezes()
    {
        return $this->hasMany(SubscriptionFreeze::class);
    }

    public function items()
    {
        return $this->hasMany(PlayerSubscriptionItem::class);
    }
}

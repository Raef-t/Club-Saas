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
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'remaining_sessions',
        'paid_amount',
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

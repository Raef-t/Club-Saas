<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PlayerSubscription extends Model
{
    use SoftDeletes, \Modules\Core\Traits\HasCreatedBy;

    protected $fillable = [
        'member_id',
        'coach_id',
        'plan_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];


    public ?\Modules\Core\DTOs\MemberDTO $member = null;

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

    public function services()
    {
        return $this->hasMany(PlayerSubscriptionService::class);
    }

    public function getIsFullyPaidAttribute()
    {
        return (float) $this->paid_amount >= (float) $this->total_amount;
    }
    
    protected $appends = ['is_fully_paid'];
}

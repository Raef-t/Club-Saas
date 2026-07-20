<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\HasCreatedBy;

class Invoice extends Model
{
    use HasFactory, HasCreatedBy;

    protected $table = 'invoices';

    protected $fillable = [
        'member_id',
        'branch_id',
        'player_subscription_id',
        'offer_id',
        'total',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function member()
    {
        // Polymorphic or cross-module mapping: we can reference the member directly
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class, 'member_id');
    }

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}

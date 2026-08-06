<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasCreatedBy;

class Invoice extends Model
{
    use HasFactory, HasCreatedBy, SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'member_id',
        'member_name',
        'branch_id',
        'player_subscription_id',
        'offer_id',
        'total',
        'status',
    ];

    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->member_name) && !empty($invoice->member_id)) {
                $member = \Modules\MemberManager\Models\Member::with('person')->find($invoice->member_id);
                if ($member && $member->person) {
                    $invoice->member_name = $member->person->full_name;
                }
            }
        });
    }

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(\Modules\MemberManager\Models\Member::class, 'member_id')->withTrashed();
    }

    public function subscription()
    {
        return $this->belongsTo(PlayerSubscription::class, 'player_subscription_id')->withTrashed();
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id')->withTrashed();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
}

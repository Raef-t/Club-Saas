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
        'code',
        'member_id',
        'branch_id',
        'player_subscription_id',
        'total',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $code = 'INV_' . str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
                while (static::where('code', $code)->exists()) {
                    $code = 'INV_' . str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
                }
                $model->code = $code;
            }
        });
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

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
}

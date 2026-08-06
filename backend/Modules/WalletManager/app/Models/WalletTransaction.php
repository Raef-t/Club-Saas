<?php

namespace Modules\WalletManager\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Authentication\Models\User;

class WalletTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type', // deposit, payment, withdrawal
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

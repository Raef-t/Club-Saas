<?php

namespace Modules\WalletManager\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Authentication\Models\Person;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'balance',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

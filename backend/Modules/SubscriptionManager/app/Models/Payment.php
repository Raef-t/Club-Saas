<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Traits\HasCreatedBy;

class Payment extends Model
{
    use HasFactory, HasCreatedBy;

    protected $table = 'payments';

    protected $fillable = [
        'receipt_number',
        'type',
        'payable_id',
        'payable_type',
        'safe_id',
        'amount',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payable()
    {
        return $this->morphTo();
    }

    public function scopeIncomes($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'out');
    }
}

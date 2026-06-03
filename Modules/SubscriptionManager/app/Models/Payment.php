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
        'invoice_id',
        'cash_register_id',
        'amount',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}

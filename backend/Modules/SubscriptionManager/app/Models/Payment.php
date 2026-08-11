<?php

namespace Modules\SubscriptionManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasCreatedBy;

class Payment extends Model
{
    use HasFactory, HasCreatedBy, SoftDeletes;

    protected $table = 'payments';

    protected $fillable = [
        'receipt_number',
        'invoice_id',
        'safe_id',
        'amount',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id')->withTrashed();
    }

    protected static function booted(): void
    {
        static::saved(function ($payment) {
            self::syncInvoiceAndSubscription($payment->invoice_id);
        });

        static::deleted(function ($payment) {
            self::syncInvoiceAndSubscription($payment->invoice_id);
        });

        static::restored(function ($payment) {
            self::syncInvoiceAndSubscription($payment->invoice_id);
        });
    }

    public static function syncInvoiceAndSubscription($invoiceId): void
    {
        $invoice = Invoice::withTrashed()->find($invoiceId);
        if (!$invoice) {
            return;
        }

        $totalPaid = self::where('invoice_id', $invoiceId)->sum('amount');

        if ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partially_paid']);
        } else {
            $invoice->update(['status' => 'unpaid']);
        }

        if ($invoice->player_subscription_id) {
            $subscription = PlayerSubscription::withTrashed()->find($invoice->player_subscription_id);
            if ($subscription) {
                $newRemaining = max(0, $subscription->total_amount - $totalPaid);
                $subscription->update([
                    'paid_amount' => $totalPaid,
                    'remaining_amount' => $newRemaining,
                ]);
            }
        }
    }
}

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
        'reason',
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
        static::created(function ($payment) {
            event(new \Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded($payment));
        });

        static::saved(function ($payment) {
            self::syncInvoiceAndSubscription($payment->invoice_id);
            self::syncWithAccountingLedger($payment, 'saved');
        });

        static::deleted(function ($payment) {
            self::syncInvoiceAndSubscription($payment->invoice_id);
            self::syncWithAccountingLedger($payment, 'deleted');
        });

        static::restored(function ($payment) {
            self::syncInvoiceAndSubscription($payment->invoice_id);
            self::syncWithAccountingLedger($payment, 'restored');
        });
    }

    public static function syncWithAccountingLedger(self $payment, string $action): void
    {
        if (!class_exists(\Modules\Accounting\Models\AccJournal::class)) {
            return;
        }

        try {
            $journal = \Modules\Accounting\Models\AccJournal::withoutGlobalScopes()
                ->where('source_type', 'payment')
                ->where('source_id', $payment->id)
                ->first();

            if ($action === 'deleted') {
                if ($journal && $journal->status !== 'cancelled') {
                    $ledgerService = app(\Modules\Accounting\Services\LedgerService::class);
                    $reason = $payment->reason ?? 'تم حذف الدفعة من سجل المدفوعات';
                    $ledgerService->cancelJournal($journal, 'إلغاء تلقائي: ' . $reason);
                }
            } elseif ($action === 'restored') {
                if ($journal && $journal->status === 'cancelled') {
                    $journal->update([
                        'status' => 'posted',
                        'notes'  => trim(($journal->notes ? $journal->notes . ' | ' : '') . 'تم استرجاع الدفعة وإعادة تفعيل السند'),
                    ]);
                }
            } elseif ($action === 'saved') {
                if ($journal && $journal->status === 'posted' && $payment->wasChanged(['amount', 'safe_id'])) {
                    $newAmount = (float) $payment->amount;
                    $safe = \Modules\Accounting\Models\AccSafe::find($payment->safe_id);
                    $isUsd = ($safe?->currency ?? 'USD') === 'USD';

                    foreach ($journal->entries as $entry) {
                        if ($entry->debit_usd > 0 || $entry->debit_syp > 0) {
                            $entry->update([
                                'account_id' => $safe?->account_id ?? $entry->account_id,
                                'debit_usd'  => $isUsd ? $newAmount : 0,
                                'debit_syp'  => !$isUsd ? $newAmount : 0,
                            ]);
                        } elseif ($entry->credit_usd > 0 || $entry->credit_syp > 0) {
                            $entry->update([
                                'credit_usd' => $isUsd ? $newAmount : 0,
                                'credit_syp' => !$isUsd ? $newAmount : 0,
                            ]);
                        }
                    }

                    if ($payment->safe_id && $payment->safe_id !== $journal->safe_id) {
                        $journal->update(['safe_id' => $payment->safe_id]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync payment with accounting ledger: ' . $e->getMessage());
        }
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

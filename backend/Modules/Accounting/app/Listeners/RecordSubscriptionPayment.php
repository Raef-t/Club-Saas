<?php

namespace Modules\Accounting\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\LedgerService;
use Modules\SubscriptionManager\Events\SubscriptionPaymentRecorded;

class RecordSubscriptionPayment
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LedgerService $ledgerService) {}

    /**
     * Handle the event.
     */
    public function handle(SubscriptionPaymentRecorded $event): void
    {
        $payment = $event->payment;

        if (!$payment->safe_id || $payment->amount <= 0) {
            return;
        }

        // Prevent duplicate journal creation if already exists for this payment
        $existing = DB::table('acc_journals')
            ->where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->exists();
        if ($existing) {
            return;
        }

        // 1. Get safe details
        $safe = DB::table('acc_safes')->where('id', $payment->safe_id)->first();
        if (!$safe) {
            return;
        }

        // 2. Ensure payment is for an invoice and get it
        if (!$payment->invoice_id) {
            return;
        }

        $invoice = DB::table('invoices')->where('id', $payment->invoice_id)->first();
        if (!$invoice) {
            return;
        }

        $branchId = $invoice->branch_id;

        // 3. Resolve the revenue account for this branch
        $revenueAccountCode = DB::table('acc_branch_settings')
            ->where('branch_id', $branchId)
            ->value('revenue_account_code') ?? '4100';

        $revenueAccountId = DB::table('acc_accounts')
            ->where('code', $revenueAccountCode)
            ->value('id');

        if (!$revenueAccountId) {
            // Fallback to general service revenue account
            $revenueAccountId = DB::table('acc_accounts')
                ->where('code', '4100')
                ->value('id');
        }

        if (!$revenueAccountId || !$safe->account_id) {
            return;
        }

        $currency = $safe->currency ?? 'USD';
        $isLocker = !empty($invoice->locker_reservation_id);
        $memoSafe = $isLocker ? ('دفعة تأجير خزانة - فاتورة رقم ' . $invoice->id) : ('دفعة اشتراك - فاتورة رقم ' . $invoice->id);
        $memoRevenue = $isLocker ? ('إيراد تأجير خزائن - فاتورة رقم ' . $invoice->id) : ('إيراد مبيعات اشتراكات - فاتورة رقم ' . $invoice->id);
        $journalDesc = $isLocker ? ('قيد تلقائي: إيراد تأجير خزانة - دفعة رقم ' . $payment->id) : ('قيد تلقائي: إيراد اشتراك لاعب - دفعة رقم ' . $payment->id);

        // 4. Construct debit and credit lines
        $lines = [];
        if ($currency === 'SYP') {
            $lines = [
                [
                    'account_id' => $safe->account_id,
                    'debit_syp'  => $payment->amount,
                    'credit_syp' => 0,
                    'memo'       => $memoSafe,
                ],
                [
                    'account_id' => $revenueAccountId,
                    'debit_syp'  => 0,
                    'credit_syp' => $payment->amount,
                    'memo'       => $memoRevenue,
                ]
            ];
        } else {
            $lines = [
                [
                    'account_id' => $safe->account_id,
                    'debit_usd'  => $payment->amount,
                    'credit_usd' => 0,
                    'memo'       => $isLocker ? ('Locker Payment - Invoice #' . $invoice->id) : ('Subscription Payment - Invoice #' . $invoice->id),
                ],
                [
                    'account_id' => $revenueAccountId,
                    'debit_usd'  => 0,
                    'credit_usd' => $payment->amount,
                    'memo'       => $isLocker ? ('Locker Rental Revenue - Invoice #' . $invoice->id) : ('Subscription Sales Revenue - Invoice #' . $invoice->id),
                ]
            ];
        }

        // 5. Post the double-entry journal
        $this->ledgerService->postJournal(
            header: [
                'type'        => 'RV', // Receipt Voucher (سند قبض)
                'date'        => now()->toDateString(),
                'description' => $journalDesc,
                'safe_id'     => $payment->safe_id,
                'branch_id'   => $branchId,
                'source_type' => 'payment',
                'source_id'   => $payment->id,
            ],
            lines: $lines,
            postImmediately: true
        );
    }
}

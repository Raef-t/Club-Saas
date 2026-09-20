<?php

namespace Modules\Accounting\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Services\LedgerService;
use Modules\SubscriptionManager\Events\LockerReservationRefunded;

class RecordLockerReservationRefund
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LedgerService $ledgerService) {}

    /**
     * Handle the event.
     */
    public function handle(LockerReservationRefunded $event): void
    {
        $reservation = $event->reservation;
        $refundAmount = (float) ($event->refundAmount ?? $reservation->refund_amount ?? 0);
        $safeId = $event->safeId ?? ($reservation->refund_safe_id ?? null);
        $reason = $event->reason ?? ($reservation->reason ?? null);
        $lockerId = $event->lockerId ?? ($reservation->locker_id ?? null);

        if ($refundAmount <= 0) {
            return;
        }

        // Prevent duplicate journal creation if already exists for this refund
        $existing = DB::table('acc_journals')
            ->where('source_type', 'locker_reservation_refund')
            ->where('source_id', $reservation->id)
            ->exists();
        if ($existing) {
            return;
        }

        // 1. Resolve branch_id
        $branchId = null;
        if ($lockerId) {
            $branchId = DB::table('lockers')->where('id', $lockerId)->value('branch_id');
        }
        if (!$branchId && !empty($reservation->invoice_id)) {
            $branchId = DB::table('invoices')->where('id', $reservation->invoice_id)->value('branch_id');
        }

        // 2. Resolve safe_id if not given: prioritize default_safe_id
        if (!$safeId && $branchId) {
            $safeId = DB::table('acc_branch_settings')
                ->where('branch_id', $branchId)
                ->value('default_safe_id');
        }

        if (!$safeId && !empty($reservation->invoice_id)) {
            $safeId = DB::table('payments')
                ->where('invoice_id', $reservation->invoice_id)
                ->whereNotNull('safe_id')
                ->where('status', 'completed')
                ->latest('id')
                ->value('safe_id');
        }

        if (!$safeId && $branchId) {
            $safeId = DB::table('acc_safes')
                ->where('branch_id', $branchId)
                ->where('currency', 'SYP')
                ->value('id')
                ?? DB::table('acc_safes')
                    ->where('branch_id', $branchId)
                    ->value('id');
        }

        if (!$safeId) {
            return;
        }

        $safe = DB::table('acc_safes')->where('id', $safeId)->first();
        if (!$safe || !$safe->account_id) {
            return;
        }

        if (!$branchId) {
            $branchId = $safe->branch_id;
        }

        // 3. Resolve the revenue account for this branch
        $revenueAccountCode = DB::table('acc_branch_settings')
            ->where('branch_id', $branchId)
            ->value('revenue_account_code') ?? '4100';

        $revenueAccountId = DB::table('acc_accounts')
            ->where('code', $revenueAccountCode)
            ->value('id');

        if (!$revenueAccountId) {
            $revenueAccountId = DB::table('acc_accounts')
                ->where('code', '4100')
                ->value('id');
        }

        if (!$revenueAccountId) {
            return;
        }

        // 4. Resolve locker info and member name for description
        $lockerNumber = '';
        if ($lockerId) {
            $lockerNumber = DB::table('lockers')->where('id', $lockerId)->value('locker_number') ?? '';
        }

        $memberName = '';
        if (!empty($reservation->member_id) && \Illuminate\Support\Facades\Schema::hasTable('members') && \Illuminate\Support\Facades\Schema::hasTable('people')) {
            $memberPerson = DB::table('members')
                ->join('people', 'members.person_id', '=', 'people.id')
                ->where('members.id', $reservation->member_id)
                ->select('people.full_name')
                ->first();
            $memberName = $memberPerson?->full_name ?? '';
        } elseif (!empty($reservation->invoice_id) && \Illuminate\Support\Facades\Schema::hasTable('invoices')) {
            $memberName = DB::table('invoices')->where('id', $reservation->invoice_id)->value('member_name') ?? '';
        }

        $currency = $safe->currency ?? 'USD';
        $lockerPart = $lockerNumber ? ('خزانة رقم ' . $lockerNumber) : 'خزانة';
        $memberPart = $memberName ? (' - المشترك: ' . $memberName) : '';
        $reasonPart = $reason ? (' - السبب: ' . $reason) : '';

        $journalDesc = 'سند صرف: استرجاع تأجير ' . $lockerPart . $memberPart . $reasonPart;
        $memoSafe = 'صرف استرداد تأجير ' . $lockerPart . $memberPart;
        $memoRevenue = 'مردودات تأجير ' . $lockerPart . $memberPart;

        // 5. Lines for Payment Voucher (PV):
        // Debit: Revenue account (4100) -> decreases revenue
        // Credit: Safe account -> decreases safe cash balance
        $lines = [];
        if ($currency === 'SYP') {
            $lines = [
                [
                    'account_id' => $revenueAccountId,
                    'debit_syp'  => $refundAmount,
                    'credit_syp' => 0,
                    'memo'       => $memoRevenue,
                ],
                [
                    'account_id' => $safe->account_id,
                    'debit_syp'  => 0,
                    'credit_syp' => $refundAmount,
                    'memo'       => $memoSafe,
                ],
            ];
        } else {
            $lines = [
                [
                    'account_id' => $revenueAccountId,
                    'debit_usd'  => $refundAmount,
                    'credit_usd' => 0,
                    'memo'       => $memoRevenue,
                ],
                [
                    'account_id' => $safe->account_id,
                    'debit_usd'  => 0,
                    'credit_usd' => $refundAmount,
                    'memo'       => $memoSafe,
                ],
            ];
        }

        // 6. Post Payment Voucher (PV)
        try {
            $this->ledgerService->postJournal(
                header: [
                    'type'        => 'PV', // Payment Voucher (سند صرف)
                    'date'        => now()->toDateString(),
                    'description' => $journalDesc,
                    'safe_id'     => $safe->id,
                    'branch_id'   => $branchId,
                    'source_type' => 'locker_reservation_refund',
                    'source_id'   => $reservation->id,
                ],
                lines: $lines,
                postImmediately: true
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to post locker reservation refund journal: ' . $e->getMessage());
        }
    }
}

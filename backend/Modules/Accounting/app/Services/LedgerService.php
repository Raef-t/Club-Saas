<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccAccount;
use Modules\Accounting\Models\AccJournal;
use Modules\Accounting\Models\AccJournalEntry;
use Modules\Accounting\Models\AccPeriod;

class LedgerService
{
    /**
     * إنشاء سند قيد جديد وترحيله فوراً أو تركه مسودة
     *
     * @param array $header — type, date, description, period_id?, counterparty_id?, safe_id?, exchange_rate?, source_type?, source_id?, notes?
     * @param array $lines  — each: account_id, debit_usd?, credit_usd?, debit_syp?, credit_syp?, memo?
     * @param bool  $postImmediately — true = ترحيل فوري، false = مسودة
     */
    public function postJournal(array $header, array $lines, bool $postImmediately = true): AccJournal
    {
        // 1. التحقق من توازن القيد (مدين = دائن)
        $this->validateBalance($lines);

        // 2. تحديد الفترة المحاسبية
        $date   = $header['date'] ?? now()->toDateString();
        $period = $this->resolvePeriod($header['period_id'] ?? null, $date);

        if ($period->isClosed()) {
            throw new \Exception('الفترة المحاسبية مغلقة. لا يمكن إضافة قيود جديدة.');
        }

        return DB::transaction(function () use ($header, $lines, $period, $date, $postImmediately) {
            // 3. تحديد معرف الفرع
            $branchId = $header['branch_id'] ?? null;
            if (!$branchId && !empty($header['safe_id'])) {
                $branchId = \Modules\Accounting\Models\AccSafe::withoutGlobalScopes()->where('id', $header['safe_id'])->value('branch_id');
            }
            if (!$branchId && !empty($header['period_id'])) {
                $branchId = $period->branch_id ?? null;
            }
            if (!$branchId) {
                $branchId = request()->header('X-Branch-ID') ?: (Auth::check() ? Auth::user()->branch_id : null);
            }

            // 4. إنشاء رأس السند
            $journal = AccJournal::create([
                'reference_number' => $this->generateReferenceNumber($header['type'] ?? 'JV'),
                'type'             => $header['type'] ?? 'JV',
                'period_id'        => $period->id,
                'date'             => $date,
                'description'      => $header['description'],
                'counterparty_id'  => $header['counterparty_id'] ?? null,
                'safe_id'          => $header['safe_id'] ?? null,
                'exchange_rate'    => $header['exchange_rate'] ?? null,
                'status'           => 'draft',
                'source_type'      => $header['source_type'] ?? null,
                'source_id'        => $header['source_id'] ?? null,
                'notes'            => $header['notes'] ?? null,
                'branch_id'        => $branchId,
            ]);

            // 4. إنشاء تفاصيل القيد
            foreach ($lines as $line) {
                AccJournalEntry::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'debit_usd'  => $line['debit_usd']  ?? 0,
                    'credit_usd' => $line['credit_usd'] ?? 0,
                    'debit_syp'  => $line['debit_syp']  ?? 0,
                    'credit_syp' => $line['credit_syp'] ?? 0,
                    'memo'       => $line['memo']        ?? null,
                ]);
            }

            // 5. ترحيل فوري إذا طُلب ذلك
            if ($postImmediately) {
                $journal->update([
                    'status'    => 'posted',
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                ]);
            }

            return $journal->load('entries.account', 'period', 'safe', 'counterparty');
        });
    }

    /**
     * ترحيل سند مسودة
     */
    public function postDraftJournal(AccJournal $journal): AccJournal
    {
        if ($journal->isPosted()) {
            throw new \Exception('السند مرحَّل مسبقاً.');
        }
        if ($journal->reversed_journal_id) {
            throw new \Exception('لا يمكن ترحيل سند معكوس.');
        }
        if ($journal->period->isClosed()) {
            throw new \Exception('الفترة المحاسبية مغلقة.');
        }

        $journal->update([
            'status'    => 'posted',
            'posted_by' => Auth::id(),
            'posted_at' => now(),
        ]);

        return $journal->refresh()->load('entries.account');
    }

    /**
     * عكس سند مرحَّل (Reversal) — السندات لا تُحذف بل تُعكس
     */
    public function reverseJournal(AccJournal $journal, string $reason): AccJournal
    {
        if (!$journal->isPosted()) {
            throw new \Exception('لا يمكن عكس سند غير مرحَّل.');
        }
        if ($journal->reversed_journal_id) {
            throw new \Exception('السند معكوس مسبقاً.');
        }

        // التحقق من أن السند الحالي ليس سنداً عاكساً لقيد آخر
        $isReversal = AccJournal::where('reversed_journal_id', $journal->id)->exists();
        if ($isReversal) {
            throw new \Exception('لا يمكن عكس سند عاكس.');
        }

        return DB::transaction(function () use ($journal, $reason) {
            // بناء أسطر القيد العاكس (تبادل المدين والدائن)
            $reversedLines = $journal->entries->map(fn($e) => [
                'account_id' => $e->account_id,
                'debit_usd'  => $e->credit_usd,
                'credit_usd' => $e->debit_usd,
                'debit_syp'  => $e->credit_syp,
                'credit_syp' => $e->debit_syp,
                'memo'       => 'عكس: ' . ($e->memo ?? ''),
            ])->toArray();

            $reversalJournal = $this->postJournal(
                header: [
                    'type'        => $journal->type,
                    'date'        => now()->toDateString(),
                    'description' => 'عكس السند: ' . $journal->reference_number . ' — ' . $reason,
                    'safe_id'     => $journal->safe_id,
                    'source_type' => $journal->source_type,
                    'source_id'   => $journal->source_id,
                    'notes'       => $reason,
                    'branch_id'   => $journal->branch_id,
                ],
                lines: $reversedLines,
                postImmediately: true
            );

            // يبقى السند الأصلي بحالة 'posted' حتى تظل بنوده مرئية في جميع
            // الاستعلامات المالية (كشف الصندوق، ميزان المراجعة، إلخ).
            // القيد العاكس يلغي تأثيره تلقائياً عبر القيد المزدوج.
            $journal->update([
                'reversed_journal_id' => $reversalJournal->id,
            ]);

            return $reversalJournal;
        });
    }

    /**
     * إلغاء سند قيد (يزيل أثره المالي تماماً من الصادر والوارد دون إنشاء قيد عكسي)
     */
    public function cancelJournal(AccJournal $journal, ?string $reason = null): AccJournal
    {
        if ($journal->status === 'cancelled') {
            throw new \Exception('السند ملغى مسبقاً.');
        }

        $journal->update([
            'status' => 'cancelled',
            'notes'  => trim(($journal->notes ? $journal->notes . ' | ' : '') . ($reason ? 'سبب الإلغاء: ' . $reason : 'تم الإلغاء')),
        ]);

        return $journal->refresh();
    }

    /**
     * جلب رصيد حساب حتى تاريخ معين
     */
    public function getAccountBalance(int $accountId, ?string $upToDate = null, ?int $branchId = null): array
    {
        $query = AccJournalEntry::where('account_id', $accountId)
            ->whereHas('journal', function ($q) use ($upToDate, $branchId) {
                $q->where('status', 'posted');
                if ($upToDate) {
                    $q->where('date', '<=', $upToDate);
                }
                if ($branchId !== null) {
                    $q->where('branch_id', $branchId);
                }
            });

        $debitUsd  = (float) $query->sum('debit_usd');
        $creditUsd = (float) $query->sum('credit_usd');
        $debitSyp  = (float) $query->sum('debit_syp');
        $creditSyp = (float) $query->sum('credit_syp');

        $account       = AccAccount::find($accountId);
        $isDebitNormal = in_array($account?->type, ['asset', 'expense']);

        return [
            'account_id'     => $accountId,
            'account_code'   => $account?->code,
            'account_name'   => $account?->name,
            'normal_balance' => $isDebitNormal ? 'debit' : 'credit',
            'debit_usd'      => $debitUsd,
            'credit_usd'     => $creditUsd,
            'balance_usd'    => $isDebitNormal ? ($debitUsd - $creditUsd) : ($creditUsd - $debitUsd),
            'debit_syp'      => $debitSyp,
            'credit_syp'     => $creditSyp,
            'balance_syp'    => $isDebitNormal ? ($debitSyp - $creditSyp) : ($creditSyp - $debitSyp),
        ];
    }

    /**
     * كشف حساب تفصيلي (Ledger Card)
     */
    public function getLedgerCard(int $accountId, string $from, string $to, ?int $branchId = null): array
    {
        $account       = AccAccount::findOrFail($accountId);
        $isDebitNormal = in_array($account->type, ['asset', 'expense']);

        $entries = AccJournalEntry::with(['journal'])
            ->where('account_id', $accountId)
            ->whereHas('journal', function ($q) use ($from, $to, $branchId) {
                $q->where('status', 'posted')->whereBetween('date', [$from, $to]);
                if ($branchId !== null) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->orderBy('created_at')
            ->get();

        $runningBalance = 0.0;

        $rows = $entries->map(function ($entry) use (&$runningBalance, $isDebitNormal) {
            $runningBalance += $isDebitNormal
                ? ($entry->debit_usd - $entry->credit_usd)
                : ($entry->credit_usd - $entry->debit_usd);

            return [
                'date'                => $entry->journal->date,
                'reference_number'    => $entry->journal->reference_number,
                'description'         => $entry->journal->description,
                'memo'                => $entry->memo,
                'debit_usd'           => $entry->debit_usd,
                'credit_usd'          => $entry->credit_usd,
                'running_balance_usd' => round($runningBalance, 4),
            ];
        });

        return [
            'account' => [
                'id'   => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
            ],
            'period'  => ['from' => $from, 'to' => $to],
            'entries' => $rows,
            'totals'  => [
                'total_debit_usd'     => (float) $entries->sum('debit_usd'),
                'total_credit_usd'    => (float) $entries->sum('credit_usd'),
                'closing_balance_usd' => round($runningBalance, 4),
            ],
        ];
    }

    // ===== Private Helpers =====

    private function validateBalance(array $lines): void
    {
        $totalDebitUsd  = collect($lines)->sum(fn($l) => $l['debit_usd']  ?? 0);
        $totalCreditUsd = collect($lines)->sum(fn($l) => $l['credit_usd'] ?? 0);
        $totalDebitSyp  = collect($lines)->sum(fn($l) => $l['debit_syp']  ?? 0);
        $totalCreditSyp = collect($lines)->sum(fn($l) => $l['credit_syp'] ?? 0);

        if (abs($totalDebitUsd - $totalCreditUsd) > 0.001) {
            throw new \Exception("القيد غير متوازن (USD): مدين={$totalDebitUsd}، دائن={$totalCreditUsd}");
        }
        if (abs($totalDebitSyp - $totalCreditSyp) > 0.01) {
            throw new \Exception("القيد غير متوازن (SYP): مدين={$totalDebitSyp}، دائن={$totalCreditSyp}");
        }
    }

    private function resolvePeriod(?int $periodId, string $date): AccPeriod
    {
        if ($periodId) {
            return AccPeriod::findOrFail($periodId);
        }

        $period = AccPeriod::where('status', 'open')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if (!$period) {
            throw new \Exception('لا توجد فترة محاسبية مفتوحة تغطي تاريخ: ' . $date);
        }

        return $period;
    }

    private function generateReferenceNumber(string $type): string
    {
        $year   = now()->format('Y');
        $prefix = config('accounting.journal_number_prefix.' . $type, $type);
        
        $maxRef = AccJournal::withoutGlobalScopes()
            ->where('reference_number', 'LIKE', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->max('reference_number');
            
        $nextNumber = 1;
        if ($maxRef) {
            $parts = explode('-', $maxRef);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextNumber = ((int) $lastPart) + 1;
            }
        }

        return $prefix . '-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}

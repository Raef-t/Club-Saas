<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Models\AccPeriod;
use Modules\Accounting\Models\AccSafe;
use Modules\Accounting\Models\AccPartner;
use Modules\Accounting\Models\AccJournal;
use Modules\Accounting\Models\AccJournalEntry;
use Modules\Accounting\Models\AccAccount;

class ReportService
{
    public function __construct(protected LedgerService $ledger) {}

    /**
     * ميزان المراجعة (Trial Balance)
     * يعرض جميع الحسابات مع إجمالي المدين والدائن للفترة
     */
    public function getTrialBalance(int $periodId, ?int $branchId = null): array
    {
        $period = AccPeriod::findOrFail($periodId);

        $totalsByAccount = AccJournalEntry::whereHas('journal', function ($q) use ($periodId, $branchId) {
            $q->where('status', 'posted')->where('period_id', $periodId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })
            ->selectRaw('account_id, SUM(debit_usd) as debit_usd, SUM(credit_usd) as credit_usd, SUM(debit_syp) as debit_syp, SUM(credit_syp) as credit_syp')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $accounts = AccAccount::active()->orderBy('code')->get();

        $rows = $accounts->map(function ($account) use ($totalsByAccount) {
            $entries = $totalsByAccount->get($account->id);

            $debitUsd  = (float) ($entries?->debit_usd  ?? 0);
            $creditUsd = (float) ($entries?->credit_usd ?? 0);
            $debitSyp  = (float) ($entries?->debit_syp  ?? 0);
            $creditSyp = (float) ($entries?->credit_syp ?? 0);

            // تخطي الحسابات ذات الرصيد الصفري
            if ($debitUsd == 0 && $creditUsd == 0 && $debitSyp == 0 && $creditSyp == 0) {
                return null;
            }

            return [
                'code'       => $account->code,
                'name'       => $account->name,
                'type'       => $account->type,
                'debit_usd'  => $debitUsd,
                'credit_usd' => $creditUsd,
                'debit_syp'  => $debitSyp,
                'credit_syp' => $creditSyp,
            ];
        })->filter()->values();

        $totalDebitUsd  = $rows->sum('debit_usd');
        $totalCreditUsd = $rows->sum('credit_usd');

        return [
            'period'   => ['id' => $period->id, 'name' => $period->name, 'status' => $period->status],
            'accounts' => $rows,
            'totals'   => [
                'total_debit_usd'  => $totalDebitUsd,
                'total_credit_usd' => $totalCreditUsd,
                'total_debit_syp'  => $rows->sum('debit_syp'),
                'total_credit_syp' => $rows->sum('credit_syp'),
                'is_balanced'      => abs($totalDebitUsd - $totalCreditUsd) < 0.01,
            ],
        ];
    }

    /**
     * قائمة الدخل (Income Statement): الإيرادات - المصاريف
     */
    public function getIncomeStatement(int $periodId, ?int $branchId = null): array
    {
        $period   = AccPeriod::findOrFail($periodId);
        $tb       = $this->getTrialBalance($periodId, $branchId);
        $accounts = collect($tb['accounts']);

        $revenues = $accounts->where('type', 'revenue')->values();
        $expenses = $accounts->where('type', 'expense')->values();

        $totalRevenueUsd = $revenues->sum(fn($a) => $a['credit_usd'] - $a['debit_usd']);
        $totalExpenseUsd = $expenses->sum(fn($a) => $a['debit_usd'] - $a['credit_usd']);
        $netIncomeUsd    = $totalRevenueUsd - $totalExpenseUsd;

        $totalRevenueSyp = $revenues->sum(fn($a) => $a['credit_syp'] - $a['debit_syp']);
        $totalExpenseSyp = $expenses->sum(fn($a) => $a['debit_syp'] - $a['credit_syp']);
        $netIncomeSyp    = $totalRevenueSyp - $totalExpenseSyp;

        return [
            'period'   => ['id' => $period->id, 'name' => $period->name],
            'revenues' => $revenues,
            'expenses' => $expenses,
            'summary'  => [
                'total_revenue_usd' => $totalRevenueUsd,
                'total_expense_usd' => $totalExpenseUsd,
                'net_income_usd'    => $netIncomeUsd,
                'total_revenue_syp' => $totalRevenueSyp,
                'total_expense_syp' => $totalExpenseSyp,
                'net_income_syp'    => $netIncomeSyp,
                'is_profitable'     => $netIncomeUsd >= 0,
            ],
        ];
    }

    /**
     * الميزانية العمومية (Balance Sheet): الأصول = الخصوم + حقوق الملكية
     */
    public function getBalanceSheet(int $periodId, ?int $branchId = null): array
    {
        $period   = AccPeriod::findOrFail($periodId);
        $tb       = $this->getTrialBalance($periodId, $branchId);
        $accounts = collect($tb['accounts']);

        $assets      = $accounts->where('type', 'asset')->values();
        $liabilities = $accounts->where('type', 'liability')->values();
        $equity      = $accounts->where('type', 'equity')->values();

        // صافي الدخل من قائمة الدخل يُضاف لحقوق الملكية
        $is        = $this->getIncomeStatement($periodId, $branchId);
        $netIncome = $is['summary']['net_income_usd'];

        $totalAssetsUsd      = $assets->sum(fn($a) => $a['debit_usd'] - $a['credit_usd']);
        $totalLiabilitiesUsd = $liabilities->sum(fn($a) => $a['credit_usd'] - $a['debit_usd']);
        $totalEquityUsd      = $equity->sum(fn($a) => $a['credit_usd'] - $a['debit_usd']) + $netIncome;

        return [
            'period'      => ['id' => $period->id, 'name' => $period->name],
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'summary'     => [
                'total_assets_usd'      => $totalAssetsUsd,
                'total_liabilities_usd' => $totalLiabilitiesUsd,
                'total_equity_usd'      => $totalEquityUsd,
                'net_income_usd'        => $netIncome,
                'is_balanced'           => abs($totalAssetsUsd - ($totalLiabilitiesUsd + $totalEquityUsd)) < 0.01,
            ],
        ];
    }

    /**
     * كشف حساب الصندوق
     */
    public function getSafeStatement(int $safeId, string $from, string $to): array
    {
        $safe = AccSafe::with('account')->findOrFail($safeId);
        $isUsd = $safe->currency === 'USD';

        // 1. حساب الرصيد الافتتاحي ما قبل تاريخ $from
        $openingEntries = AccJournalEntry::where('account_id', $safe->account_id)
            ->whereHas('journal', function ($q) use ($from, $safe) {
                $q->where('status', 'posted')
                  ->where('date', '<', $from)
                  ->where(function ($sq) use ($safe) {
                      $sq->where('safe_id', $safe->id);
                      if ($safe->branch_id) {
                          $sq->orWhere(function ($ssq) use ($safe) {
                              $ssq->whereNull('safe_id')->where('branch_id', $safe->branch_id);
                          });
                      }
                  });
            })
            ->selectRaw('SUM(debit_usd) as debit_usd, SUM(credit_usd) as credit_usd, SUM(debit_syp) as debit_syp, SUM(credit_syp) as credit_syp')
            ->first();

        $openingBalance = $isUsd
            ? (float)(($openingEntries->debit_usd ?? 0) - ($openingEntries->credit_usd ?? 0))
            : (float)(($openingEntries->debit_syp ?? 0) - ($openingEntries->credit_syp ?? 0));

        // 2. جلب جميع حركات الصندوق في النطاق المحدد
        $entries = AccJournalEntry::where('account_id', $safe->account_id)
            ->whereHas('journal', function ($q) use ($from, $to, $safe) {
                $q->where('status', 'posted')
                  ->whereBetween('date', [$from, $to])
                  ->where(function ($sq) use ($safe) {
                      $sq->where('safe_id', $safe->id);
                      if ($safe->branch_id) {
                          $sq->orWhere(function ($ssq) use ($safe) {
                              $ssq->whereNull('safe_id')->where('branch_id', $safe->branch_id);
                          });
                      }
                  });
            })
            ->with(['journal'])
            ->get()
            ->sortBy(fn($entry) => $entry->journal ? $entry->journal->date : now());

        $totalIn = 0.0;
        $totalOut = 0.0;
        $runningBalance = $openingBalance;

        $rows = $entries->map(function ($entry) use ($isUsd, &$totalIn, &$totalOut, &$runningBalance) {
            $debit  = (float) ($isUsd ? $entry->debit_usd : $entry->debit_syp);
            $credit = (float) ($isUsd ? $entry->credit_usd : $entry->credit_syp);
            $totalIn  += $debit;
            $totalOut += $credit;
            $runningBalance += ($debit - $credit);

            $sourceType = $entry->journal ? $entry->journal->source_type : null;
            $sourceId   = $entry->journal ? $entry->journal->source_id : null;
            $refNumber  = $entry->journal ? $entry->journal->reference_number : '';

            return [
                'id'                  => $entry->id,
                'date'                => $entry->journal && $entry->journal->date ? $entry->journal->date->toDateString() : '',
                'number'              => $refNumber,
                'reference_number'    => $refNumber,
                'type'                => $entry->journal ? $entry->journal->type : '',
                'description'         => $entry->journal ? $entry->journal->description : '',
                'memo'                => $entry->memo,
                'in'                  => $debit,
                'out'                 => $credit,
                'debit_usd'           => $debit,   // وارد للصندوق
                'credit_usd'          => $credit,  // صادر من الصندوق
                'amount'              => $debit > 0 ? $debit : $credit,
                'running_balance'     => round($runningBalance, 2),
                'source_type'         => $sourceType,
                'source_id'           => $sourceId,
                'journal_id'          => $entry->journal ? $entry->journal->id : null,
                'reversed_journal_id' => $entry->journal ? $entry->journal->reversed_journal_id : null,
                'is_reversal'         => $entry->journal ? (bool) $entry->journal->reversed_journal_id : false,
            ];
        })->values();

        return [
            'safe'             => ['id' => $safe->id, 'name' => $safe->name, 'currency' => $safe->currency],
            'period'           => ['from' => $from, 'to' => $to],
            'opening_balance'  => round($openingBalance, 2),
            'total_in'         => round($totalIn, 2),
            'total_out'        => round($totalOut, 2),
            'closing_balance'  => round($runningBalance, 2),
            'movements'        => $rows,
            'entries'          => $rows,
            'totals'           => [
                'total_in'        => round($totalIn, 2),
                'total_out'       => round($totalOut, 2),
                'net_balance'     => round($totalIn - $totalOut, 2),
            ],
        ];
    }

    /**
     * كشف حساب الشريك (رأس المال + نصيب الأرباح - المسحوبات)
     */
    public function getPartnerStatement(int $partnerId, int $periodId): array
    {
        $partner = AccPartner::with(['capitalAccount', 'drawingsAccount'])->findOrFail($partnerId);
        $period  = AccPeriod::findOrFail($periodId);

        $capitalBalance  = $this->ledger->getAccountBalance(
            $partner->capital_account_id,
            $period->end_date ? $period->end_date->toDateString() : null
        );
        
        $drawingsUsd = 0;
        $cumulativeDrawingsUsd = 0;
        if ($partner->drawings_account_id) {
            // Period Drawings
            $drawingsEntries = AccJournalEntry::where('account_id', $partner->drawings_account_id)
                ->whereHas('journal', function ($q) use ($periodId) {
                    $q->where('status', 'posted')->where('period_id', $periodId);
                })
                ->selectRaw('SUM(debit_usd) as debit_usd, SUM(credit_usd) as credit_usd')
                ->first();

            $debit = (float) ($drawingsEntries->debit_usd ?? 0);
            $credit = (float) ($drawingsEntries->credit_usd ?? 0);
            $drawingsUsd = $debit - $credit;

            // Cumulative Drawings
            $drawingsEntriesAll = AccJournalEntry::where('account_id', $partner->drawings_account_id)
                ->whereHas('journal', function ($q) use ($period) {
                    $q->where('status', 'posted');
                    if ($period->end_date) {
                        $q->where('date', '<=', $period->end_date->toDateString());
                    }
                })
                ->selectRaw('SUM(debit_usd) as debit_usd, SUM(credit_usd) as credit_usd')
                ->first();

            $debitAll = (float) ($drawingsEntriesAll->debit_usd ?? 0);
            $creditAll = (float) ($drawingsEntriesAll->credit_usd ?? 0);
            $cumulativeDrawingsUsd = $debitAll - $creditAll;
        }

        $branchId = $partner->branch_id ?? null;
        $incomeStatement = $this->getIncomeStatement($periodId);
        $netIncome       = $incomeStatement['summary']['net_income_usd'];
        $partnerShare    = round($netIncome * ($partner->profit_share_pct / 100), 4);

        // Cumulative Net Income & Share (all periods up to $periodId)
        $periods = AccPeriod::where('id', '<=', $periodId)->get();
        $cumulativeNetIncome = 0.0;
        foreach ($periods as $p) {
            $incomeStmt = $this->getIncomeStatement($p->id);
            $cumulativeNetIncome += $incomeStmt['summary']['net_income_usd'];
        }
        $cumulativePartnerShare = round($cumulativeNetIncome * ($partner->profit_share_pct / 100), 4);

        return [
            'partner'           => [
                'id'               => $partner->id,
                'name'             => $partner->name,
                'profit_share_pct' => $partner->profit_share_pct,
                'joined_at'        => $partner->joined_at,
            ],
            'period'                        => ['id' => $period->id, 'name' => $period->name],
            'capital_balance_usd'          => $capitalBalance['balance_usd'],
            'drawings_usd'                 => $drawingsUsd,
            'cumulative_drawings_usd'      => $cumulativeDrawingsUsd,
            'net_income_usd'               => $netIncome,
            'partner_share_usd'            => $partnerShare,
            'cumulative_partner_share_usd' => $cumulativePartnerShare,
            'net_equity_usd'               => $capitalBalance['balance_usd'] + $cumulativePartnerShare - $cumulativeDrawingsUsd,
            'period_net_equity_usd'        => $partnerShare - $drawingsUsd,
        ];
    }
}

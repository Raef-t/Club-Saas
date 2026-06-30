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
    public function getTrialBalance(int $periodId): array
    {
        $period = AccPeriod::findOrFail($periodId);

        $accounts = AccAccount::active()->orderBy('code')->get();

        $rows = $accounts->map(function ($account) use ($periodId) {
            $entries = AccJournalEntry::where('account_id', $account->id)
                ->whereHas('journal', fn($q) => $q->where('status', 'posted')->where('period_id', $periodId))
                ->selectRaw('SUM(debit_usd) as debit_usd, SUM(credit_usd) as credit_usd, SUM(debit_syp) as debit_syp, SUM(credit_syp) as credit_syp')
                ->first();

            $debitUsd  = (float) ($entries->debit_usd  ?? 0);
            $creditUsd = (float) ($entries->credit_usd ?? 0);
            $debitSyp  = (float) ($entries->debit_syp  ?? 0);
            $creditSyp = (float) ($entries->credit_syp ?? 0);

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
    public function getIncomeStatement(int $periodId): array
    {
        $period   = AccPeriod::findOrFail($periodId);
        $tb       = $this->getTrialBalance($periodId);
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
    public function getBalanceSheet(int $periodId): array
    {
        $period   = AccPeriod::findOrFail($periodId);
        $tb       = $this->getTrialBalance($periodId);
        $accounts = collect($tb['accounts']);

        $assets      = $accounts->where('type', 'asset')->values();
        $liabilities = $accounts->where('type', 'liability')->values();
        $equity      = $accounts->where('type', 'equity')->values();

        // صافي الدخل من قائمة الدخل يُضاف لحقوق الملكية
        $is        = $this->getIncomeStatement($periodId);
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

        // Fetch all journal entries for this safe's account in posted journals within the date range
        $entries = AccJournalEntry::where('account_id', $safe->account_id)
            ->whereHas('journal', function ($q) use ($from, $to) {
                $q->where('status', 'posted')
                  ->whereBetween('date', [$from, $to]);
            })
            ->with('journal')
            ->get()
            ->sortBy(fn($entry) => $entry->journal->date);

        $totalInUsd  = 0.0;
        $totalOutUsd = 0.0;
        $isUsd = $safe->currency === 'USD';

        $rows = $entries->map(function ($entry) use ($isUsd, &$totalInUsd, &$totalOutUsd) {
            $debit  = (float) ($isUsd ? $entry->debit_usd : $entry->debit_syp);
            $credit = (float) ($isUsd ? $entry->credit_usd : $entry->credit_syp);
            $totalInUsd  += $debit;
            $totalOutUsd += $credit;

            return [
                'date'             => $entry->journal->date ? $entry->journal->date->toDateString() : '',
                'reference_number' => $entry->journal->reference_number,
                'type'             => $entry->journal->type,
                'description'      => $entry->journal->description,
                'debit_usd'        => $debit,   // وارد للصندوق
                'credit_usd'       => $credit,  // صادر من الصندوق
            ];
        })->values();

        return [
            'safe'    => ['id' => $safe->id, 'name' => $safe->name, 'currency' => $safe->currency],
            'period'  => ['from' => $from, 'to' => $to],
            'entries' => $rows,
            'totals'  => [
                'total_in_usd'    => $totalInUsd,
                'total_out_usd'   => $totalOutUsd,
                'net_balance_usd' => $totalInUsd - $totalOutUsd,
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

        $capitalBalance  = $this->ledger->getAccountBalance($partner->capital_account_id);
        
        $drawingsBalance = $partner->drawings_account_id
            ? $this->ledger->getAccountBalance($partner->drawings_account_id)
            : null;

        // حساب المسحوبات بطبيعتها المدينة (Normal Debit): مدين - دائن
        $drawingsUsd = $drawingsBalance
            ? ($drawingsBalance['debit_usd'] - $drawingsBalance['credit_usd'])
            : 0;

        $incomeStatement = $this->getIncomeStatement($periodId);
        $netIncome       = $incomeStatement['summary']['net_income_usd'];
        $partnerShare    = round($netIncome * ($partner->profit_share_pct / 100), 4);

        return [
            'partner'           => [
                'id'               => $partner->id,
                'name'             => $partner->name,
                'profit_share_pct' => $partner->profit_share_pct,
                'joined_at'        => $partner->joined_at,
            ],
            'period'            => ['id' => $period->id, 'name' => $period->name],
            'capital_balance_usd'   => $capitalBalance['balance_usd'],
            'drawings_usd'          => $drawingsUsd,
            'net_income_usd'        => $netIncome,
            'partner_share_usd'     => $partnerShare,
            'net_equity_usd'        => $capitalBalance['balance_usd'] + $partnerShare - $drawingsUsd,
        ];
    }
}

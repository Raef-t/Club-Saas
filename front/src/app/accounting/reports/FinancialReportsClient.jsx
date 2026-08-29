"use client";

import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { useFinancialReports } from "./useFinancialReports";

export default function FinancialReportsClient({ initialPeriods = [] }) {
  const {
    activeTab,
    setActiveTab,
    selectedPeriodId,
    setSelectedPeriodId,
    periods,
    branches,
    selectedBranchId,
    trialBalance,
    incomeStatement,
    balanceSheet,
    isLoading,
  } = useFinancialReports({ initialPeriods });

  const activeBranch = branches?.find((b) => String(b.id) === String(selectedBranchId));
  const branchName = selectedBranchId && selectedBranchId !== "all" ? (activeBranch?.name || `فرع #${selectedBranchId}`) : "كافة الفروع";

  return (
    <div className="space-y-6" dir="rtl">
      {/* Period & Tab Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-wrap items-center gap-3">
          <div className="flex items-center gap-2">
            <label className="text-xs font-semibold text-app-muted-light">الفترة المالية:</label>
            <select
              value={selectedPeriodId}
              onChange={(e) => setSelectedPeriodId(e.target.value)}
              className="h-10 rounded-xl border border-app-line bg-app-card px-3 text-xs font-medium text-app-text outline-none focus:border-app-yellow"
            >
              {periods.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.status === "open" ? "مفتوحة" : p.status === "closed" ? "مغلقة" : "مقفلة"})
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-2 rounded-xl border border-app-line bg-app-card px-3 py-2 text-xs">
            <span className="text-app-muted shrink-0">نطاق الفرع:</span>
            <span className="font-semibold text-app-yellow">{branchName}</span>
          </div>
        </div>

        {/* Tab Switcher */}
        <div className="flex rounded-xl border border-app-line/40 bg-app-card p-1">
          <button
            onClick={() => setActiveTab("trial-balance")}
            className={`rounded-lg px-4 py-1.5 text-xs font-medium transition ${
              activeTab === "trial-balance"
                ? "bg-app-yellow text-app-bg font-bold shadow"
                : "text-app-muted hover:text-app-text"
            }`}
          >
            ميزان المراجعة
          </button>
          <button
            onClick={() => setActiveTab("income-statement")}
            className={`rounded-lg px-4 py-1.5 text-xs font-medium transition ${
              activeTab === "income-statement"
                ? "bg-app-yellow text-app-bg font-bold shadow"
                : "text-app-muted hover:text-app-text"
            }`}
          >
            قائمة الدخل (الأرباح والخسائر)
          </button>
          <button
            onClick={() => setActiveTab("balance-sheet")}
            className={`rounded-lg px-4 py-1.5 text-xs font-medium transition ${
              activeTab === "balance-sheet"
                ? "bg-app-yellow text-app-bg font-bold shadow"
                : "text-app-muted hover:text-app-text"
            }`}
          >
            الميزانية العمومية
          </button>
        </div>
      </div>

      {/* Content Area */}
      {isLoading ? (
        <div className="flex h-72 items-center justify-center rounded-2xl border border-app-line/40 bg-app-panel">
          <LoadingSpinner />
        </div>
      ) : activeTab === "trial-balance" ? (
        <TrialBalanceView data={trialBalance} />
      ) : activeTab === "income-statement" ? (
        <IncomeStatementView data={incomeStatement} />
      ) : (
        <BalanceSheetView data={balanceSheet} />
      )}
    </div>
  );
}

function TrialBalanceView({ data = {} }) {
  const rows = data.accounts || data.data || [];
  const totals = data.totals || {};
  const totalDebit = Number(totals.total_debit_usd ?? data.total_debit ?? 0);
  const totalCredit = Number(totals.total_credit_usd ?? data.total_credit ?? 0);
  const isBalanced = totals.is_balanced ?? Math.abs(totalDebit - totalCredit) < 0.01;

  return (
    <div className="space-y-6">
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">ميزان المراجعة بالأرصدة والمجاميع</h2>
            <p className="text-xs text-app-muted">
              استعراض كافة الحسابات المدينة والدائنة للتحقق من توازن الأستاذ المالي
            </p>
          </div>
          <div>
            {isBalanced ? (
              <span className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-400">
                <span className="size-2 rounded-full bg-emerald-400" />
                ميزان المراجعة متوازن ✓
              </span>
            ) : (
              <span className="inline-flex items-center gap-1.5 rounded-xl bg-rose-500/10 border border-rose-500/20 px-3 py-1 text-xs font-bold text-rose-400">
                <span className="size-2 rounded-full bg-rose-400" />
                غير متوازن ✕
              </span>
            )}
          </div>
        </div>

        {rows.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد حركات أو حسابات مرحلة في هذه الفترة.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">الكود</th>
                  <th className="p-3">اسم الحساب</th>
                  <th className="p-3">النوع</th>
                  <th className="p-3 text-left">الرصيد المدين (USD)</th>
                  <th className="p-3 text-left">الرصيد الدائن (USD)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {rows.map((row, idx) => {
                  const debit = Number(row.debit_usd || 0);
                  const credit = Number(row.credit_usd || 0);

                  return (
                    <tr key={row.code || idx} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-mono font-bold text-app-yellow">{row.code}</td>
                      <td className="p-3 font-medium">{row.name}</td>
                      <td className="p-3 text-app-muted">{row.type}</td>
                      <td className="p-3 text-left font-mono font-bold text-emerald-400">
                        {debit > 0 ? `$${debit.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-3 text-left font-mono font-bold text-rose-400">
                        {credit > 0 ? `$${credit.toLocaleString()}` : "-"}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
              <tfoot className="bg-app-card-soft font-bold border-t border-app-line/40 text-xs">
                <tr>
                  <td colSpan={3} className="p-3 text-app-text">المجموع الإجمالي العام:</td>
                  <td className="p-3 text-left font-mono text-emerald-400">${totalDebit.toLocaleString()}</td>
                  <td className="p-3 text-left font-mono text-rose-400">${totalCredit.toLocaleString()}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

function IncomeStatementView({ data = {} }) {
  const summary = data.summary || {};
  const totalRevenues = Number(summary.total_revenue_usd ?? data.total_revenues_usd ?? 0);
  const totalExpenses = Number(summary.total_expense_usd ?? data.total_expenses_usd ?? 0);
  const netIncome = Number(summary.net_income_usd ?? (totalRevenues - totalExpenses));
  const isProfitable = summary.is_profitable ?? (netIncome >= 0);

  const revenuesList = data.revenues || [];
  const expensesList = data.expenses || [];

  return (
    <div className="space-y-6">
      {/* Summary Banner */}
      <div className={`rounded-2xl border p-5 text-center ${
        isProfitable ? "bg-emerald-500/10 border-emerald-500/30" : "bg-rose-500/10 border-rose-500/30"
      }`}>
        <span className="text-xs text-app-muted block font-medium">النتيجة المالية الصافية للفترة:</span>
        <h2 className={`text-2xl font-bold font-mono mt-1 ${isProfitable ? "text-emerald-400" : "text-rose-400"}`}>
          {isProfitable ? `صافي أرباح: $${netIncome.toLocaleString()}` : `صافي خسائر: $${Math.abs(netIncome).toLocaleString()}`}
        </h2>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Revenues Card */}
        <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
          <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
            <h3 className="text-base font-bold text-emerald-400">الإيرادات المحصلة</h3>
            <span className="font-mono font-bold text-emerald-400 text-lg">${totalRevenues.toLocaleString()}</span>
          </div>

          <div className="space-y-2 text-xs">
            {revenuesList.length === 0 ? (
              <p className="text-app-muted text-center py-6">لا توجد إيرادات مسجلة بالفترة.</p>
            ) : (
              revenuesList.map((item, idx) => {
                const amount = Number(item.credit_usd ? (item.credit_usd - (item.debit_usd || 0)) : item.amount || item.balance || 0);
                return (
                  <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                    <span className="text-app-text font-medium">{item.code ? `${item.code} - ${item.name}` : item.name}</span>
                    <span className="font-mono font-bold text-emerald-400">
                      ${amount.toLocaleString()}
                    </span>
                  </div>
                );
              })
            )}
          </div>
        </div>

        {/* Expenses Card */}
        <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
          <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
            <h3 className="text-base font-bold text-rose-400">المصروفات والتكاليف</h3>
            <span className="font-mono font-bold text-rose-400 text-lg">${totalExpenses.toLocaleString()}</span>
          </div>

          <div className="space-y-2 text-xs">
            {expensesList.length === 0 ? (
              <p className="text-app-muted text-center py-6">لا توجد مصروفات مسجلة بالفترة.</p>
            ) : (
              expensesList.map((item, idx) => {
                const amount = Number(item.debit_usd ? (item.debit_usd - (item.credit_usd || 0)) : item.amount || item.balance || 0);
                return (
                  <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                    <span className="text-app-text font-medium">{item.code ? `${item.code} - ${item.name}` : item.name}</span>
                    <span className="font-mono font-bold text-rose-400">
                      ${amount.toLocaleString()}
                    </span>
                  </div>
                );
              })
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

function BalanceSheetView({ data = {} }) {
  const summary = data.summary || {};
  const totalAssets = Number(summary.total_assets_usd ?? data.total_assets_usd ?? 0);
  const totalLiabilities = Number(summary.total_liabilities_usd ?? 0);
  const totalEquity = Number(summary.total_equity_usd ?? 0);
  const netIncome = Number(summary.net_income_usd ?? 0);
  const isBalanced = summary.is_balanced ?? (Math.abs(totalAssets - (totalLiabilities + totalEquity)) < 0.01);

  const assets = data.assets || [];
  const liabilities = data.liabilities || [];
  const equity = data.equity || [];

  return (
    <div className="space-y-6">
      {/* Balance Equation Status */}
      <div className="rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4 flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-3 text-xs">
          <span className="text-app-muted font-medium">معادلة الميزانية:</span>
          <span className="font-mono font-bold text-blue-400">الأصول (${totalAssets.toLocaleString()})</span>
          <span className="text-app-muted">=</span>
          <span className="font-mono font-bold text-amber-400">الخصوم (${totalLiabilities.toLocaleString()})</span>
          <span className="text-app-muted">+</span>
          <span className="font-mono font-bold text-purple-400">حقوق الملكية والأرباح (${totalEquity.toLocaleString()})</span>
        </div>
        <div>
          {isBalanced ? (
            <span className="inline-flex items-center gap-1 rounded-xl bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-400">
              ✓ الميزانية العمومية متوازنة
            </span>
          ) : (
            <span className="inline-flex items-center gap-1 rounded-xl bg-rose-500/10 border border-rose-500/20 px-3 py-1 text-xs font-bold text-rose-400">
              ✕ غير متوازنة
            </span>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Assets Side */}
        <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
          <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
            <h3 className="text-base font-bold text-blue-400">جانب الأصول (الموجودات)</h3>
            <span className="font-mono font-bold text-blue-400 text-lg">${totalAssets.toLocaleString()}</span>
          </div>

          <div className="space-y-2 text-xs">
            {assets.length === 0 ? (
              <p className="text-app-muted text-center py-6">لا توجد أصول مسجلة.</p>
            ) : (
              assets.map((item, idx) => {
                const amount = Number(item.debit_usd ? (item.debit_usd - (item.credit_usd || 0)) : item.balance || item.amount || 0);
                return (
                  <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                    <span className="text-app-text font-medium">{item.code ? `${item.code} - ${item.name}` : item.name}</span>
                    <span className="font-mono font-bold text-blue-400">
                      ${amount.toLocaleString()}
                    </span>
                  </div>
                );
              })
            )}
          </div>
        </div>

        {/* Liabilities & Equity Side */}
        <div className="space-y-6">
          {/* Liabilities */}
          <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
            <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
              <h3 className="text-base font-bold text-amber-400">الخصوم (الالتزامات والذمم)</h3>
              <span className="font-mono font-bold text-amber-400 text-lg">${totalLiabilities.toLocaleString()}</span>
            </div>

            <div className="space-y-2 text-xs">
              {liabilities.length === 0 ? (
                <p className="text-app-muted text-center py-4">لا توجد التزامات مسجلة.</p>
              ) : (
                liabilities.map((item, idx) => {
                  const amount = Number(item.credit_usd ? (item.credit_usd - (item.debit_usd || 0)) : item.balance || item.amount || 0);
                  return (
                    <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                      <span className="text-app-text font-medium">{item.code ? `${item.code} - ${item.name}` : item.name}</span>
                      <span className="font-mono font-bold text-amber-400">
                        ${amount.toLocaleString()}
                      </span>
                    </div>
                  );
                })
              )}
            </div>
          </div>

          {/* Equity */}
          <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
            <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
              <h3 className="text-base font-bold text-purple-400">حقوق الملكية وصافي الدخل</h3>
              <span className="font-mono font-bold text-purple-400 text-lg">${totalEquity.toLocaleString()}</span>
            </div>

            <div className="space-y-2 text-xs">
              {equity.map((item, idx) => {
                const amount = Number(item.credit_usd ? (item.credit_usd - (item.debit_usd || 0)) : item.balance || item.amount || 0);
                return (
                  <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                    <span className="text-app-text font-medium">{item.code ? `${item.code} - ${item.name}` : item.name}</span>
                    <span className="font-mono font-bold text-purple-400">
                      ${amount.toLocaleString()}
                    </span>
                  </div>
                );
              })}

              <div className="flex justify-between py-2 border-b border-app-line/20 font-bold bg-purple-500/5 px-2 rounded-lg">
                <span className="text-purple-300">صافي ربح / خسارة الفترة (من قائمة الدخل)</span>
                <span className={`font-mono ${netIncome >= 0 ? "text-emerald-400" : "text-rose-400"}`}>
                  ${netIncome.toLocaleString()}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

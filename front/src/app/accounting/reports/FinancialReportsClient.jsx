"use client";

import StatsGrid from "@/components/ui/StatsGrid";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import {
  TipJarIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";
import { useFinancialReports } from "./useFinancialReports";

export default function FinancialReportsClient({ initialPeriods = [] }) {
  const {
    activeTab,
    setActiveTab,
    selectedPeriodId,
    setSelectedPeriodId,
    periods,
    trialBalance,
    incomeStatement,
    balanceSheet,
    isLoading,
  } = useFinancialReports({ initialPeriods });

  return (
    <div className="space-y-6" dir="rtl">
      {/* Period & Tab Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex items-center gap-3">
          <label className="text-xs font-semibold text-app-muted-light">الفترة المالية المحاسبية:</label>
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

function TrialBalanceView({ data }) {
  const rows = data.accounts || data.data || [];
  const totalDebit = Number(data.total_debit || 0);
  const totalCredit = Number(data.total_credit || 0);
  const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;

  const stats = [
    { label: "إجمالي الأرصدة المدينة", value: `$${totalDebit.toLocaleString()}` },
    { label: "إجمالي الأرصدة الدائنة", value: `$${totalCredit.toLocaleString()}` },
    { label: "حالة التوازن", value: isBalanced ? "✓ ميزان متوازن" : "✕ غير متوازن" },
  ];

  return (
    <div className="space-y-6">
      <StatsGrid items={stats} />

      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">ميزان المراجعة بالأرصدة والمجاميع</h2>
            <p className="text-xs text-app-muted">
              استعراض كافة الحسابات وحركاتها ورصيدها الختامي خلال الفترة
            </p>
          </div>
        </div>

        {rows.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد بيانات متاحة لميزان المراجعة في هذه الفترة.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">الكود</th>
                  <th className="p-3">اسم الحساب</th>
                  <th className="p-3">النوع</th>
                  <th className="p-3 text-left">مجموع مدين (USD)</th>
                  <th className="p-3 text-left">مجموع دائن (USD)</th>
                  <th className="p-3 text-left">رصيد مدين (USD)</th>
                  <th className="p-3 text-left">رصيد دائن (USD)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {rows.map((row, idx) => (
                  <tr key={row.id || idx} className="hover:bg-app-card-soft/40 transition">
                    <td className="p-3 font-mono font-bold text-app-yellow">{row.code}</td>
                    <td className="p-3 font-medium">{row.name}</td>
                    <td className="p-3 text-app-muted">{row.type}</td>
                    <td className="p-3 text-left font-mono text-emerald-400">
                      {Number(row.period_debit || 0) > 0 ? `$${Number(row.period_debit).toLocaleString()}` : "-"}
                    </td>
                    <td className="p-3 text-left font-mono text-rose-400">
                      {Number(row.period_credit || 0) > 0 ? `$${Number(row.period_credit).toLocaleString()}` : "-"}
                    </td>
                    <td className="p-3 text-left font-mono font-bold text-emerald-400">
                      {Number(row.closing_debit || 0) > 0 ? `$${Number(row.closing_debit).toLocaleString()}` : "-"}
                    </td>
                    <td className="p-3 text-left font-mono font-bold text-rose-400">
                      {Number(row.closing_credit || 0) > 0 ? `$${Number(row.closing_credit).toLocaleString()}` : "-"}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot className="bg-app-card-soft font-bold border-t border-app-line/40 text-xs">
                <tr>
                  <td colSpan={3} className="p-3 text-app-text">المجموع الإجمالي العام:</td>
                  <td className="p-3 text-left font-mono text-emerald-400">${totalDebit.toLocaleString()}</td>
                  <td className="p-3 text-left font-mono text-rose-400">${totalCredit.toLocaleString()}</td>
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

function IncomeStatementView({ data }) {
  const totalRevenues = Number(data.total_revenues || 0);
  const totalExpenses = Number(data.total_expenses || 0);
  const netIncome = Number(data.net_income || totalRevenues - totalExpenses);
  const isProfitable = netIncome >= 0;

  const revenuesList = data.revenues || [];
  const expensesList = data.expenses || [];

  const stats = [
    { label: "إجمالي الإيرادات", value: `$${totalRevenues.toLocaleString()}` },
    { label: "إجمالي المصروفات", value: `$${totalExpenses.toLocaleString()}` },
    { label: isProfitable ? "صافي الربح" : "صافي الخسارة", value: `$${Math.abs(netIncome).toLocaleString()}` },
  ];

  return (
    <div className="space-y-6">
      <StatsGrid items={stats} />

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Revenues Card */}
        <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
          <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
            <h3 className="text-base font-bold text-emerald-400">الإيرادات المحصلة</h3>
            <span className="font-mono font-bold text-emerald-400">${totalRevenues.toLocaleString()}</span>
          </div>

          <div className="space-y-2 text-xs">
            {revenuesList.length === 0 ? (
              <p className="text-app-muted text-center py-6">لا توجد إيرادات مسجلة بالفترة.</p>
            ) : (
              revenuesList.map((item, idx) => (
                <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                  <span className="text-app-text">{item.name || item.code}</span>
                  <span className="font-mono font-bold text-emerald-400">
                    ${Number(item.amount || item.balance || 0).toLocaleString()}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Expenses Card */}
        <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
          <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
            <h3 className="text-base font-bold text-rose-400">المصروفات والتكاليف</h3>
            <span className="font-mono font-bold text-rose-400">${totalExpenses.toLocaleString()}</span>
          </div>

          <div className="space-y-2 text-xs">
            {expensesList.length === 0 ? (
              <p className="text-app-muted text-center py-6">لا توجد مصروفات مسجلة بالفترة.</p>
            ) : (
              expensesList.map((item, idx) => (
                <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                  <span className="text-app-text">{item.name || item.code}</span>
                  <span className="font-mono font-bold text-rose-400">
                    ${Number(item.amount || item.balance || 0).toLocaleString()}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      {/* Net Summary Banner */}
      <div className={`rounded-2xl border p-5 text-center ${
        isProfitable ? "bg-emerald-500/10 border-emerald-500/30" : "bg-rose-500/10 border-rose-500/30"
      }`}>
        <span className="text-xs text-app-muted block">النتيجة المالية النهائية للفترة:</span>
        <h2 className={`text-2xl font-bold font-mono mt-1 ${isProfitable ? "text-emerald-400" : "text-rose-400"}`}>
          {isProfitable ? `صافي ربح: $${netIncome.toLocaleString()}` : `صافي خسارة: $${Math.abs(netIncome).toLocaleString()}`}
        </h2>
      </div>
    </div>
  );
}

function BalanceSheetView({ data }) {
  const totalAssets = Number(data.total_assets || 0);
  const totalLiabilities = Number(data.total_liabilities || 0);
  const totalEquity = Number(data.total_equity || 0);
  const totalLiabEquity = totalLiabilities + totalEquity;
  const isBalanced = Math.abs(totalAssets - totalLiabEquity) < 0.01;

  const assets = data.assets || [];
  const liabilities = data.liabilities || [];
  const equity = data.equity || [];

  const stats = [
    { label: "إجمالي الأصول (Assets)", value: `$${totalAssets.toLocaleString()}` },
    { label: "إجمالي الخصوم (Liabilities)", value: `$${totalLiabilities.toLocaleString()}` },
    { label: "حقوق الملكية (Equity)", value: `$${totalEquity.toLocaleString()}` },
    { label: "معادلة الميزانية", value: isBalanced ? "✓ الأصول = الخصوم + الملكية" : "✕ غير متوازنة" },
  ];

  return (
    <div className="space-y-6">
      <StatsGrid items={stats} />

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Assets Side */}
        <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
          <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
            <h3 className="text-base font-bold text-blue-400">جانب الأصول (الموجودات)</h3>
            <span className="font-mono font-bold text-blue-400">${totalAssets.toLocaleString()}</span>
          </div>

          <div className="space-y-2 text-xs">
            {assets.length === 0 ? (
              <p className="text-app-muted text-center py-6">لا توجد أصول مسجلة.</p>
            ) : (
              assets.map((item, idx) => (
                <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                  <span className="text-app-text">{item.name || item.code}</span>
                  <span className="font-mono font-bold text-blue-400">
                    ${Number(item.balance || item.amount || 0).toLocaleString()}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Liabilities & Equity Side */}
        <div className="space-y-6">
          {/* Liabilities */}
          <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
            <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
              <h3 className="text-base font-bold text-amber-400">الخصوم (الالتزامات والذمم)</h3>
              <span className="font-mono font-bold text-amber-400">${totalLiabilities.toLocaleString()}</span>
            </div>

            <div className="space-y-2 text-xs">
              {liabilities.length === 0 ? (
                <p className="text-app-muted text-center py-4">لا توجد التزامات مسجلة.</p>
              ) : (
                liabilities.map((item, idx) => (
                  <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                    <span className="text-app-text">{item.name || item.code}</span>
                    <span className="font-mono font-bold text-amber-400">
                      ${Number(item.balance || item.amount || 0).toLocaleString()}
                    </span>
                  </div>
                ))
              )}
            </div>
          </div>

          {/* Equity */}
          <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
            <div className="flex items-center justify-between border-b border-app-line/30 pb-3 mb-4">
              <h3 className="text-base font-bold text-purple-400">حقوق الملكية ورأس المال</h3>
              <span className="font-mono font-bold text-purple-400">${totalEquity.toLocaleString()}</span>
            </div>

            <div className="space-y-2 text-xs">
              {equity.length === 0 ? (
                <p className="text-app-muted text-center py-4">لا توجد حقوق ملكية مسجلة.</p>
              ) : (
                equity.map((item, idx) => (
                  <div key={idx} className="flex justify-between py-2 border-b border-app-line/20">
                    <span className="text-app-text">{item.name || item.code}</span>
                    <span className="font-mono font-bold text-purple-400">
                      ${Number(item.balance || item.amount || 0).toLocaleString()}
                    </span>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

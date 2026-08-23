"use client";

import { useState } from "react";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { Field } from "@/components/forms/FormControls";
import {
  FileDownIcon,
  PlusIcon,
} from "@/components/icons/Icons";
import { useJournals, JOURNAL_STATUSES } from "../journals/useJournals";
import ExpenseFormModal from "./ExpenseFormModal";
import JournalDetailsModal from "../journals/JournalDetailsModal";
import JournalActionModal from "../journals/JournalActionModal";

export default function ExpensesClient({
  initialJournals = [],
  initialAccounts = [],
  initialSafes = [],
  initialCounterparties = [],
}) {
  const [isExpenseModalOpen, setIsExpenseModalOpen] = useState(false);

  const {
    journals,
    accounts,
    safes,
    counterparties,
    statusFilter,
    setStatusFilter,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    search,
    setSearch,
    isLoading,
    selectedJournal,
    actionConfig,
    formErrors,
    isSaving,
    isProcessingAction,
    openDetailsModal,
    closeDetailsModal,
    openActionModal,
    closeActionModal,
    handleSaveJournal,
    handleExecuteAction,
  } = useJournals({
    initialJournals,
    initialAccounts,
    initialSafes,
    initialCounterparties,
    defaultType: "PV",
  });

  // Filter only PV vouchers for this page
  const expenseJournals = journals.filter((j) => j.type === "PV");

  return (
    <div className="space-y-6" dir="rtl">

      {/* Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-1 flex-wrap items-center gap-3">
          <div className="w-full sm:w-64 md:w-80">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث برقم السند، البيان، الحساب، الصندوق..."
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center gap-2 rounded-xl border border-app-line bg-app-card px-3 py-2 text-xs">
              <span className="text-app-muted shrink-0">من:</span>
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                className="bg-transparent text-app-text outline-none text-xs cursor-pointer font-mono"
              />
            </div>
            <div className="flex items-center gap-2 rounded-xl border border-app-line bg-app-card px-3 py-2 text-xs">
              <span className="text-app-muted shrink-0">إلى:</span>
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                className="bg-transparent text-app-text outline-none text-xs cursor-pointer font-mono"
              />
            </div>

            {(fromDate || toDate || search) && (
              <button
                type="button"
                onClick={() => {
                  setFromDate("");
                  setToDate("");
                  setSearch("");
                }}
                className="rounded-xl border border-app-line bg-app-card px-3 py-2 text-xs text-app-muted hover:text-rose-400 hover:border-rose-500/40 transition shrink-0"
                title="إلغاء الفلترة"
              >
                مسح الفلاتر
              </button>
            )}
          </div>
        </div>

        <Button
          variant="primary"
          onClick={() => setIsExpenseModalOpen(true)}
          className="flex items-center gap-2 shrink-0"
        >
          <FileDownIcon className="size-4" />
          <span>تسجيل مصروف جديد</span>
        </Button>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">سجل المصاريف وسندات الصرف</h2>
            <p className="text-xs text-app-muted">
              كافة المصروفات الثابتة والتشغيلية المنصرفة من صناديق الفرع
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {expenseJournals.length} سند صرف
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : expenseJournals.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد سندات صرف مسجلة في هذا الفرع.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">رقم السند</th>
                  <th className="p-3">التاريخ</th>
                  <th className="p-3">البيان والشرح</th>
                  <th className="p-3">الصندوق المنصرف منه</th>
                  <th className="p-3 text-left">المبلغ (USD)</th>
                  <th className="p-3 text-left">المبلغ (SYP)</th>
                  <th className="p-3">الحالة</th>
                  <th className="p-3 text-left">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {expenseJournals.map((journal) => {
                  const statusInfo = JOURNAL_STATUSES[journal.status] || {
                    label: journal.status,
                    color: "bg-app-card",
                  };

                  const amountUsd = (journal.entries || []).reduce(
                    (sum, e) => sum + Number(e.debit_usd || 0),
                    0
                  );
                  const amountSyp = (journal.entries || []).reduce(
                    (sum, e) => sum + Number(e.debit_syp || 0),
                    0
                  );

                  return (
                    <tr key={journal.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-mono font-bold text-app-yellow">
                        {journal.number || `#${journal.id}`}
                      </td>
                      <td className="p-3 font-mono text-app-muted whitespace-nowrap">
                        {journal.date}
                      </td>
                      <td className="p-3 max-w-sm truncate text-app-text font-medium" title={journal.description}>
                        {journal.description}
                      </td>
                      <td className="p-3 text-app-muted-light">{journal.safe?.name || "صندوق عام"}</td>
                      <td className="p-3 text-left font-mono font-bold text-rose-400">
                        {amountUsd > 0 ? `$${amountUsd.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-3 text-left font-mono font-bold text-rose-400">
                        {amountSyp > 0 ? `${amountSyp.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-3">
                        <span
                          className={`rounded-full px-2.5 py-0.5 text-[10px] border font-semibold ${statusInfo.color}`}
                        >
                          {statusInfo.label}
                        </span>
                      </td>
                      <td className="p-3 text-left">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => openDetailsModal(journal)}
                            className="rounded-lg border border-app-line/40 bg-app-card-soft px-2.5 py-1 text-xs text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                          >
                            التفاصيل
                          </button>

                          {journal.status === "draft" && (
                            <button
                              onClick={() => openActionModal("post", journal)}
                              className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-xs text-emerald-400 hover:bg-emerald-500/20 transition"
                            >
                              ترحيل
                            </button>
                          )}

                          {journal.status !== "cancelled" && (
                            <button
                              onClick={() => openActionModal("cancel", journal)}
                              className="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2 py-1 text-xs text-rose-400 hover:bg-rose-500/20 transition"
                              title="إلغاء السند وإزالة أثره المالي"
                            >
                              إلغاء
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Quick Expense Form Modal */}
      <ExpenseFormModal
        isOpen={isExpenseModalOpen}
        onClose={() => setIsExpenseModalOpen(false)}
        accounts={accounts}
        safes={safes}
        counterparties={counterparties}
        onSave={async (data) => {
          const success = await handleSaveJournal(data);
          if (success) setIsExpenseModalOpen(false);
        }}
        isLoading={isSaving}
        errors={formErrors}
      />

      {/* Details & Action Modals */}
      <JournalDetailsModal
        isOpen={Boolean(selectedJournal)}
        onClose={closeDetailsModal}
        journal={selectedJournal}
        onOpenAction={openActionModal}
      />

      <JournalActionModal
        isOpen={actionConfig.isOpen}
        onClose={closeActionModal}
        type={actionConfig.type}
        journal={actionConfig.journal}
        onExecute={handleExecuteAction}
        isLoading={isProcessingAction}
      />
    </div>
  );
}

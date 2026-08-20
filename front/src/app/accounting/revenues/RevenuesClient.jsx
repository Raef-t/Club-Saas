"use client";

import { useState } from "react";
import StatsGrid from "@/components/ui/StatsGrid";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { Field } from "@/components/forms/FormControls";
import {
  FileUpIcon,
  PlusIcon,
} from "@/components/icons/Icons";
import { useJournals, JOURNAL_STATUSES } from "../journals/useJournals";
import RevenueFormModal from "./RevenueFormModal";
import JournalDetailsModal from "../journals/JournalDetailsModal";
import JournalActionModal from "../journals/JournalActionModal";

export default function RevenuesClient({
  initialJournals = [],
  initialAccounts = [],
  initialSafes = [],
}) {
  const [isRevenueModalOpen, setIsRevenueModalOpen] = useState(false);

  const {
    journals,
    accounts,
    safes,
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
    defaultType: "RV",
  });

  // Filter only RV vouchers for this page
  const revenueJournals = journals.filter((j) => j.type === "RV");

  const totalUsd = revenueJournals.reduce(
    (sum, j) =>
      sum + (j.entries || []).reduce((s, e) => s + Number(e.credit_usd || 0), 0),
    0
  );

  const totalSyp = revenueJournals.reduce(
    (sum, j) =>
      sum + (j.entries || []).reduce((s, e) => s + Number(e.credit_syp || 0), 0),
    0
  );

  const statsItems = [
    { label: "إجمالي سندات القبض", value: revenueJournals.length },
    { label: "إجمالي الإيرادات (USD)", value: `$${totalUsd.toLocaleString()}` },
    { label: "إجمالي الإيرادات (SYP)", value: `${totalSyp.toLocaleString()} ل.س` },
    { label: "سندات بانتظار الترحيل", value: revenueJournals.filter((j) => j.status === "draft").length },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Stats */}
      <StatsGrid items={statsItems} />

      {/* Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-1 flex-wrap items-center gap-3 min-w-[280px]">
          <div className="w-full sm:w-72">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث في الإيرادات..."
            />
          </div>

          <div className="flex items-center gap-2">
            <div className="w-36">
              <Field
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e?.target?.value ?? e)}
                placeholder="من تاريخ"
              />
            </div>
            <div className="w-36">
              <Field
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e?.target?.value ?? e)}
                placeholder="إلى تاريخ"
              />
            </div>
          </div>
        </div>

        <Button
          variant="primary"
          onClick={() => setIsRevenueModalOpen(true)}
          className="flex items-center gap-2"
        >
          <FileUpIcon className="size-4" />
          <span>تسجيل إيراد جديد</span>
        </Button>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">سجل الإيرادات وسندات القبض</h2>
            <p className="text-xs text-app-muted">
              كافة المقبوضات والإيرادات المحصلة في صناديق الفرع
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {revenueJournals.length} سند قبض
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : revenueJournals.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد سندات قبض مسجلة في هذا الفرع.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">رقم السند</th>
                  <th className="p-3">التاريخ</th>
                  <th className="p-3">البيان والشرح</th>
                  <th className="p-3">الصندوق المستلم</th>
                  <th className="p-3 text-left">المبلغ (USD)</th>
                  <th className="p-3 text-left">المبلغ (SYP)</th>
                  <th className="p-3">الحالة</th>
                  <th className="p-3 text-left">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {revenueJournals.map((journal) => {
                  const statusInfo = JOURNAL_STATUSES[journal.status] || {
                    label: journal.status,
                    color: "bg-app-card",
                  };

                  const amountUsd = (journal.entries || []).reduce(
                    (sum, e) => sum + Number(e.credit_usd || 0),
                    0
                  );
                  const amountSyp = (journal.entries || []).reduce(
                    (sum, e) => sum + Number(e.credit_syp || 0),
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
                      <td className="p-3 text-left font-mono font-bold text-emerald-400">
                        {amountUsd > 0 ? `$${amountUsd.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-3 text-left font-mono font-bold text-emerald-400">
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

      {/* Quick Revenue Form Modal */}
      <RevenueFormModal
        isOpen={isRevenueModalOpen}
        onClose={() => setIsRevenueModalOpen(false)}
        accounts={accounts}
        safes={safes}
        onSave={async (data) => {
          const success = await handleSaveJournal(data);
          if (success) setIsRevenueModalOpen(false);
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

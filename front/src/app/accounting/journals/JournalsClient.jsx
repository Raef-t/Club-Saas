"use client";

import StatsGrid from "@/components/ui/StatsGrid";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import { Field } from "@/components/forms/FormControls";
import {
  EyeIcon,
  FolderPlusIcon,
  PlusIcon,
  TipJarIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";

import { useJournals, JOURNAL_TYPES, JOURNAL_STATUSES } from "./useJournals";
import JournalFormModal from "./JournalFormModal";
import JournalDetailsModal from "./JournalDetailsModal";
import JournalActionModal from "./JournalActionModal";

export default function JournalsClient({
  initialJournals = [],
  initialAccounts = [],
  initialSafes = [],
}) {
  const {
    journals,
    pagination,
    accounts,
    safes,
    typeFilter,
    setTypeFilter,
    statusFilter,
    setStatusFilter,
    fromDate,
    setFromDate,
    toDate,
    setToDate,
    search,
    setSearch,
    isLoading,
    isFormOpen,
    selectedJournal,
    actionConfig,
    formErrors,
    isSaving,
    isProcessingAction,
    openCreateModal,
    closeFormModal,
    openDetailsModal,
    closeDetailsModal,
    openActionModal,
    closeActionModal,
    handleSaveJournal,
    handleExecuteAction,
  } = useJournals({ initialJournals, initialAccounts, initialSafes });

  const jvCount = journals.filter((j) => j.type === "JV").length;
  const rvCount = journals.filter((j) => j.type === "RV").length;
  const pvCount = journals.filter((j) => j.type === "PV").length;

  const statsItems = [
    { label: "إجمالي السندات", value: pagination.total || journals.length },
    { label: "قيود عامة (JV)", value: jvCount },
    { label: "سندات قبض (RV)", value: rvCount },
    { label: "سندات صرف (PV)", value: pvCount },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Stats Overview */}
      <StatsGrid items={statsItems} />

      {/* Toolbar: Search, Filters, Date Range, Add Button */}
      <div className="space-y-3 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="w-full sm:w-80">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث برقم السند أو البيان..."
            />
          </div>

          <div className="flex flex-wrap items-center gap-2">
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
            {(fromDate || toDate) && (
              <button
                onClick={() => {
                  setFromDate("");
                  setToDate("");
                }}
                className="text-xs text-app-muted hover:text-app-yellow"
              >
                مسح التواريخ
              </button>
            )}
          </div>

          <Button variant="primary" onClick={openCreateModal} className="flex items-center gap-2">
            <FolderPlusIcon className="size-4" />
            <span>إنشاء سند قيد جديد</span>
          </Button>
        </div>

        {/* Filter Pills */}
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-app-line/30 pt-3">
          <div className="flex flex-wrap items-center gap-1.5">
            <span className="text-xs text-app-muted ml-2">نوع السند:</span>
            <button
              onClick={() => setTypeFilter("all")}
              className={`rounded-xl px-2.5 py-1 text-xs font-medium transition ${
                typeFilter === "all"
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card text-app-muted hover:text-app-text"
              }`}
            >
              الكل
            </button>
            {Object.entries(JOURNAL_TYPES).map(([key, item]) => (
              <button
                key={key}
                onClick={() => setTypeFilter(key)}
                className={`rounded-xl px-2.5 py-1 text-xs font-medium transition ${
                  typeFilter === key
                    ? "bg-app-yellow text-app-bg font-bold shadow"
                    : "bg-app-card text-app-muted hover:text-app-text"
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>

          <div className="flex flex-wrap items-center gap-1.5">
            <span className="text-xs text-app-muted ml-2">الحالة:</span>
            <button
              onClick={() => setStatusFilter("all")}
              className={`rounded-xl px-2.5 py-1 text-xs font-medium transition ${
                statusFilter === "all"
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card text-app-muted hover:text-app-text"
              }`}
            >
              الكل
            </button>
            {Object.entries(JOURNAL_STATUSES).map(([key, item]) => (
              <button
                key={key}
                onClick={() => setStatusFilter(key)}
                className={`rounded-xl px-2.5 py-1 text-xs font-medium transition ${
                  statusFilter === key
                    ? "bg-app-yellow text-app-bg font-bold shadow"
                    : "bg-app-card text-app-muted hover:text-app-text"
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Main Table */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">سجل سندات القيود اليومية</h2>
            <p className="text-xs text-app-muted">
              استعراض كافة السندات المرحلية، سندات القبض والصرف، وحالات الترحيل المالي
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {journals.length} سند
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : journals.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد سندات قيود مطابقة لمعايير البحث في هذا الفرع.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">رقم السند</th>
                  <th className="p-3">التاريخ</th>
                  <th className="p-3">النوع</th>
                  <th className="p-3">البيان والشرح</th>
                  <th className="p-3 text-left">إجمالي القيمة (USD)</th>
                  <th className="p-3 text-left">إجمالي القيمة (SYP)</th>
                  <th className="p-3">الحالة</th>
                  <th className="p-3 text-left">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {journals.map((journal) => {
                  const typeInfo = JOURNAL_TYPES[journal.type] || { label: journal.type, badge: "bg-app-card" };
                  const statusInfo = JOURNAL_STATUSES[journal.status] || { label: journal.status, color: "bg-app-card" };

                  const totalDebitUsd = (journal.entries || []).reduce((sum, e) => sum + Number(e.debit_usd || 0), 0);
                  const totalDebitSyp = (journal.entries || []).reduce((sum, e) => sum + Number(e.debit_syp || 0), 0);

                  return (
                    <tr key={journal.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-mono font-bold text-app-yellow">
                        {journal.number || `#${journal.id}`}
                      </td>
                      <td className="p-3 font-mono text-app-muted whitespace-nowrap">{journal.date}</td>
                      <td className="p-3">
                        <span className={`rounded-full px-2 py-0.5 text-[10px] border font-medium ${typeInfo.badge}`}>
                          {typeInfo.label}
                        </span>
                      </td>
                      <td className="p-3 max-w-sm truncate text-app-text font-medium" title={journal.description}>
                        {journal.description}
                      </td>
                      <td className="p-3 text-left font-mono font-medium text-emerald-400">
                        {totalDebitUsd > 0 ? `$${totalDebitUsd.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-3 text-left font-mono text-emerald-400">
                        {totalDebitSyp > 0 ? `${totalDebitSyp.toLocaleString()}` : "-"}
                      </td>
                      <td className="p-3">
                        <span className={`rounded-full px-2.5 py-0.5 text-[10px] border font-semibold ${statusInfo.color}`}>
                          {statusInfo.label}
                        </span>
                      </td>
                      <td className="p-3 text-left">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => openDetailsModal(journal)}
                            className="rounded-lg border border-app-line/40 bg-app-card-soft px-2.5 py-1 text-xs text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition flex items-center gap-1"
                          >
                            <span>التفاصيل</span>
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

      {/* Journal Create Modal */}
      <JournalFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        accounts={accounts}
        safes={safes}
        onSave={handleSaveJournal}
        isLoading={isSaving}
        errors={formErrors}
      />

      {/* Journal Details Modal */}
      <JournalDetailsModal
        isOpen={Boolean(selectedJournal)}
        onClose={closeDetailsModal}
        journal={selectedJournal}
        onOpenAction={openActionModal}
      />

      {/* Journal Action Modal */}
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

"use client";

import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import {
  FolderPlusIcon,
  HandCoinsIcon,
  PencilIcon,
  TipJarIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";

import { useSafes } from "./useSafes";
import SafeFormModal from "./SafeFormModal";
import SafeStatementModal from "./SafeStatementModal";
import SafeReconciliationModal from "./SafeReconciliationModal";

export default function SafesClient({ initialSafes = [], initialAccounts = [] }) {
  const {
    safes,
    filteredSafes,
    accounts,
    branches,
    selectedBranchId,
    stats,
    search,
    setSearch,
    currencyFilter,
    setCurrencyFilter,
    isLoading,
    isFormOpen,
    editingSafe,
    statementSafe,
    reconciliationSafe,
    formErrors,
    isSaving,
    isReconciling,
    openCreateModal,
    openEditModal,
    closeFormModal,
    openStatementModal,
    closeStatementModal,
    openReconciliationModal,
    closeReconciliationModal,
    handleSaveSafe,
    handleSaveReconciliation,
  } = useSafes({ initialSafes, initialAccounts });

  return (
    <div className="space-y-6" dir="rtl">
      {/* Toolbar: Search, Filters, Add Button */}
      <div className="flex flex-col gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div className="flex flex-col sm:flex-row sm:items-center gap-3 flex-1 min-w-0">
          <div className="w-full sm:w-80 shrink-0">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث باسم الصندوق أو الملاحظات..."
              className="!min-w-0 w-full"
            />
          </div>

          <div className="flex items-center gap-1.5 flex-wrap">
            <button
              type="button"
              onClick={() => setCurrencyFilter("all")}
              className={`rounded-xl px-3 py-2 text-xs font-medium transition shrink-0 ${
                currencyFilter === "all"
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card-soft text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
              }`}
            >
              كافة العملات
            </button>
            <button
              type="button"
              onClick={() => setCurrencyFilter("USD")}
              className={`rounded-xl px-3 py-2 text-xs font-medium transition shrink-0 ${
                currencyFilter === "USD"
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card-soft text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
              }`}
            >
              دولار (USD)
            </button>
            <button
              type="button"
              onClick={() => setCurrencyFilter("SYP")}
              className={`rounded-xl px-3 py-2 text-xs font-medium transition shrink-0 ${
                currencyFilter === "SYP"
                  ? "bg-app-yellow text-app-bg font-bold shadow"
                  : "bg-app-card-soft text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
              }`}
            >
              ليرة سورية (SYP)
            </button>
          </div>
        </div>

        <Button variant="primary" onClick={openCreateModal} className="flex items-center justify-center gap-2 shrink-0">
          <FolderPlusIcon className="size-4" />
          <span>إضافة صندوق جديد</span>
        </Button>
      </div>

      {/* Safes Grid */}
      {isLoading ? (
        <div className="flex h-64 items-center justify-center">
          <LoadingSpinner />
        </div>
      ) : filteredSafes.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-app-line/60 bg-app-panel p-12 text-center text-app-muted">
          لا توجد صناديق أو خزائن مالية مطابقة لمعايير البحث في هذا الفرع.
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filteredSafes.map((safe) => {
            const currencySymbol = safe.currency === "USD" ? "$" : "ل.س";

            return (
              <div
                key={safe.id}
                className="group relative flex flex-col justify-between rounded-2xl border border-app-line/40 bg-app-panel p-5 transition-all duration-200 hover:border-app-yellow/40 hover:shadow-lg"
              >
                <div>
                  {/* Top Badges */}
                  <div className="flex items-center justify-between gap-2 pb-3 border-b border-app-line/30">
                    <div className="flex items-center gap-2">
                      <span className="rounded-lg bg-app-yellow-soft px-2.5 py-1 text-xs font-bold text-app-yellow border border-app-yellow/30 font-mono">
                        {safe.currency}
                      </span>
                      {safe.branch?.name && (
                        <span className="rounded-lg bg-blue-500/10 px-2 py-0.5 text-[11px] font-medium text-blue-400 border border-blue-500/20">
                          {safe.branch.name}
                        </span>
                      )}
                    </div>

                    <div className="flex items-center gap-2">
                      {safe.is_default && (
                        <span className="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-400 border border-emerald-500/20">
                          صندوق افتراضي
                        </span>
                      )}
                      {safe.is_active ? (
                        <span className="inline-flex items-center gap-1 text-[11px] text-emerald-400">
                          <span className="size-1.5 rounded-full bg-emerald-400 animate-pulse" />
                          نشط
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-[11px] text-rose-400">
                          <span className="size-1.5 rounded-full bg-rose-400" />
                          معطل
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Safe Title & Details */}
                  <div className="mt-4">
                    <h3 className="text-base font-bold text-app-text group-hover:text-app-yellow transition-colors">
                      {safe.name}
                    </h3>
                    <p className="mt-1 text-xs text-app-muted line-clamp-2">
                      {safe.notes || "صندوق المقبوضات والمدفوعات اليومية للفرع"}
                    </p>
                  </div>

                  {/* Live Balance Display */}
                  <div className="mt-4 rounded-xl bg-app-card-soft/80 p-3 border border-app-line/40 flex items-center justify-between">
                    <span className="text-xs text-app-muted font-medium">الرصيد الفعلي الحالي:</span>
                    <span className={`text-base font-bold font-mono ${Number(safe.current_balance || 0) >= 0 ? "text-emerald-400" : "text-rose-400"}`}>
                      {currencySymbol} {Number(safe.current_balance || 0).toLocaleString()}
                    </span>
                  </div>

                  {/* Account Mapping info */}
                  <div className="mt-2.5 rounded-xl bg-app-card-soft/40 p-2.5 space-y-1 text-xs">
                    <div className="flex justify-between text-app-muted">
                      <span>الحساب المقابل:</span>
                      <span className="font-mono text-app-text font-medium">
                        {safe.account?.code ? `${safe.account.code} - ${safe.account.name}` : "غير مربوط بحساب"}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Bottom Actions */}
                <div className="mt-5 flex items-center justify-between gap-2 border-t border-app-line/30 pt-3">
                  <div className="flex items-center gap-1.5">
                    <button
                      onClick={() => openStatementModal(safe)}
                      className="flex items-center gap-1 rounded-lg border border-app-line/40 bg-app-card-soft px-2.5 py-1.5 text-xs text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                      title="كشف حركة الصندوق"
                    >
                      <TipJarIcon className="size-3.5" />
                      <span>كشف حركة</span>
                    </button>

                    <button
                      onClick={() => openReconciliationModal(safe)}
                      className="flex items-center gap-1 rounded-lg border border-app-line/40 bg-app-card-soft px-2.5 py-1.5 text-xs text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                      title="مطابقة وتسوية الصندوق"
                    >
                      <HandCoinsIcon className="size-3.5" />
                      <span>تسوية</span>
                    </button>
                  </div>

                  <button
                    onClick={() => openEditModal(safe)}
                    className="grid size-8 place-items-center rounded-lg border border-app-line/40 bg-app-card-soft text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                    title="تعديل الصندوق"
                  >
                    <PencilIcon className="size-4" />
                  </button>

                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Safe Create/Edit Modal */}
      <SafeFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        safe={editingSafe}
        accounts={accounts}
        branches={branches}
        currentBranchId={selectedBranchId}
        onSave={handleSaveSafe}
        isLoading={isSaving}
        errors={formErrors}
      />

      {/* Safe Statement Modal */}
      <SafeStatementModal
        isOpen={Boolean(statementSafe)}
        onClose={closeStatementModal}
        safe={statementSafe}
      />

      {/* Safe Reconciliation Modal */}
      <SafeReconciliationModal
        isOpen={Boolean(reconciliationSafe)}
        onClose={closeReconciliationModal}
        safe={reconciliationSafe}
        onSave={handleSaveReconciliation}
        isLoading={isReconciling}
      />
    </div>
  );
}

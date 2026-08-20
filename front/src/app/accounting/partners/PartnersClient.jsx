"use client";

import StatsGrid from "@/components/ui/StatsGrid";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import {
  HandCoinsIcon,
  PencilIcon,
  PlusIcon,
  TipJarIcon,
  TrashIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";

import { usePartners } from "./usePartners";
import PartnerFormModal from "./PartnerFormModal";
import PartnerTransactionModal from "./PartnerTransactionModal";
import PartnerStatementModal from "./PartnerStatementModal";

export default function PartnersClient({ initialPartners = [], initialSafes = [] }) {
  const {
    partners,
    filteredPartners,
    safes,
    branches,
    selectedBranchId,
    stats,
    search,
    setSearch,
    isLoading,
    isFormOpen,
    editingPartner,
    statementPartner,
    transactionConfig,
    deleteConfirmPartner,
    setDeleteConfirmPartner,
    deleteConfirmation,
    setDeleteConfirmation,
    formErrors,
    isSaving,
    isProcessingTx,
    isDeleting,
    openCreateModal,
    openEditModal,
    closeFormModal,
    openStatementModal,
    closeStatementModal,
    openTransactionModal,
    closeTransactionModal,
    handleSavePartner,
    handleDeletePartner,
    handleExecuteTransaction,
  } = usePartners({ initialPartners, initialSafes });

  const statsItems = [
    { label: "إجمالي الشركاء", value: stats.total },
    { label: "الشركاء النشطين", value: stats.active },
    { label: "إجمالي حصص الأرباح", value: `${stats.totalActiveShare.toFixed(1)}%` },
    { label: "النسبة المتاحة المتبقية", value: `${stats.remainingAvailableShare.toFixed(1)}%` },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      {/* Stats */}
      <StatsGrid items={statsItems} />

      {/* Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="w-full sm:w-80">
          <SearchInput
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="البحث باسم الشريك أو الملاحظات..."
          />
        </div>

        <Button
          variant="primary"
          onClick={openCreateModal}
          disabled={stats.remainingAvailableShare <= 0}
          className="flex items-center gap-2"
        >
          <PlusIcon className="size-4" />
          <span>تسجيل شريك جديد</span>
        </Button>
      </div>

      {/* Partners Grid */}
      {isLoading ? (
        <div className="flex h-64 items-center justify-center">
          <LoadingSpinner />
        </div>
      ) : filteredPartners.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-app-line/60 bg-app-panel p-12 text-center text-app-muted">
          لا يوجد شركاء مسجلين في هذا الفرع.
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filteredPartners.map((partner) => (
            <div
              key={partner.id}
              className="group relative flex flex-col justify-between rounded-2xl border border-app-line/40 bg-app-panel p-5 transition-all duration-200 hover:border-app-yellow/40 hover:shadow-lg"
            >
              <div>
                {/* Header info */}
                <div className="flex items-center justify-between gap-2 pb-3 border-b border-app-line/30">
                  <span className="rounded-lg bg-app-yellow-soft px-2.5 py-1 text-xs font-bold text-app-yellow border border-app-yellow/30 font-mono">
                    حصة الأرباح: {Number(partner.profit_share_pct)}%
                  </span>

                  <div>
                    {partner.is_active ? (
                      <span className="inline-flex items-center gap-1 text-[11px] text-emerald-400">
                        <span className="size-1.5 rounded-full bg-emerald-400" />
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

                <div className="mt-4">
                  <h3 className="text-base font-bold text-app-text group-hover:text-app-yellow transition-colors">
                    {partner.name}
                  </h3>
                  <p className="mt-1 text-xs text-app-muted font-mono">
                    تاريخ الانضمام: {partner.joined_at ? partner.joined_at.split("T")[0] : "-"}
                  </p>
                </div>

                {/* Associated Accounts */}
                <div className="mt-4 rounded-xl bg-app-card-soft/60 p-3 space-y-2 text-xs">
                  <div className="flex justify-between text-app-muted">
                    <span>حساب رأس المال:</span>
                    <span className="font-mono text-emerald-400 font-medium">
                      {partner.capital_account?.code || "310x"}
                    </span>
                  </div>
                  <div className="flex justify-between text-app-muted">
                    <span>حساب المسحوبات:</span>
                    <span className="font-mono text-rose-400 font-medium">
                      {partner.drawings_account?.code || "330x"}
                    </span>
                  </div>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-app-line/30 pt-3">
                <div className="flex flex-wrap items-center gap-1.5">
                  <button
                    onClick={() => openTransactionModal("deposit", partner)}
                    className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-xs text-emerald-400 hover:bg-emerald-500/20 transition"
                  >
                    + إيداع رأس مال
                  </button>

                  <button
                    onClick={() => openTransactionModal("withdraw", partner)}
                    className="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2 py-1 text-xs text-rose-400 hover:bg-rose-500/20 transition"
                  >
                    - مسحوبات
                  </button>

                  <button
                    onClick={() => openStatementModal(partner)}
                    className="rounded-lg border border-app-line/40 bg-app-card-soft px-2 py-1 text-xs text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                  >
                    كشف حساب
                  </button>
                </div>

                <div className="flex items-center gap-1">
                  <button
                    onClick={() => openEditModal(partner)}
                    className="grid size-7 place-items-center rounded-lg border border-app-line/40 bg-app-card-soft text-app-muted-light hover:border-app-yellow hover:text-app-yellow transition"
                    title="تعديل"
                  >
                    <PencilIcon className="size-3.5" />
                  </button>

                  <button
                    onClick={() => setDeleteConfirmPartner(partner)}
                    className="grid size-7 place-items-center rounded-lg border border-app-line/40 bg-app-card-soft text-app-muted-light hover:border-rose-500 hover:text-rose-400 transition"
                    title="حذف"
                  >
                    <TrashIcon className="size-3.5" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Partner Form Modal */}
      <PartnerFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        partner={editingPartner}
        branches={branches}
        currentBranchId={selectedBranchId}
        remainingShare={stats.remainingAvailableShare}
        onSave={handleSavePartner}
        isLoading={isSaving}
        errors={formErrors}
      />

      {/* Transaction Modal (Deposit / Withdraw) */}
      <PartnerTransactionModal
        isOpen={transactionConfig.isOpen}
        onClose={closeTransactionModal}
        type={transactionConfig.type}
        partner={transactionConfig.partner}
        safes={safes}
        onExecute={handleExecuteTransaction}
        isLoading={isProcessingTx}
      />

      {/* Statement Modal */}
      <PartnerStatementModal
        isOpen={Boolean(statementPartner)}
        onClose={closeStatementModal}
        partner={statementPartner}
      />

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        open={Boolean(deleteConfirmPartner)}
        title="تأكيد حذف الشريك"
        message={`هل أنت متأكد من رغبتك في حذف الشريك "${deleteConfirmPartner?.name}" وحساباته؟ لن ينجح الحذف في حال وجود حركات مالية مسجلة مسبقاً.`}
        requiredConfirmation="delete"
        confirmationValue={deleteConfirmation}
        onConfirmationChange={setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        confirmText="حذف الشريك"
        onConfirm={handleDeletePartner}
        onClose={() => setDeleteConfirmPartner(null)}
        isLoading={isDeleting}
        variant="danger"
      />
    </div>
  );
}

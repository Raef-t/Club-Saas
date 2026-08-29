"use client";

import StatsGrid from "@/components/ui/StatsGrid";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import {
  HandCoinsIcon,
  PlusIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import { useSalaries } from "./useSalaries";
import SalaryPaymentFormModal from "./SalaryPaymentFormModal";

export default function SalariesClient({
  initialPayments = [],
  initialStaff = [],
  initialSafes = [],
  initialPeriods = [],
}) {
  const {
    payments,
    staffList,
    safes,
    periods,
    unpaidPayslips,
    stats,
    search,
    setSearch,
    periodFilter,
    setPeriodFilter,
    roleFilter,
    setRoleFilter,
    isLoading,
    isFormOpen,
    deletePayment,
    setDeletePayment,
    deleteConfirmation,
    setDeleteConfirmation,
    formErrors,
    isSaving,
    isDeleting,
    openCreateModal,
    closeFormModal,
    handleSavePayment,
    handleDeletePayment,
  } = useSalaries({
    initialPayments,
    initialStaff,
    initialSafes,
    initialPeriods,
  });

  return (
    <div className="space-y-6" dir="rtl">

      {/* Toolbar */}
      <div className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-app-line/40 bg-app-card-soft/40 p-4">
        <div className="flex flex-wrap items-center gap-3">
          <div className="w-full sm:w-80">
            <SearchInput
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="البحث باسم الموظف أو المدرب..."
            />
          </div>

          <div className="flex items-center gap-2">
            <select
              value={periodFilter}
              onChange={(e) => setPeriodFilter(e.target.value)}
              className="h-10 rounded-xl border border-app-line bg-app-card px-3 text-xs text-app-text outline-none focus:border-app-yellow"
            >
              <option value="all">كافة الفترات المالية</option>
              {periods.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.name} ({p.status === "open" ? "مفتوحة" : p.status === "closed" ? "مغلقة" : p.status})
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-2">
            <select
              value={roleFilter}
              onChange={(e) => setRoleFilter(e.target.value)}
              className="h-10 rounded-xl border border-app-line bg-app-card px-3 text-xs text-app-text outline-none focus:border-app-yellow"
            >
              <option value="all">كافة الكوادر (مدربين وموظفين)</option>
              <option value="coach">المدربين فقط</option>
              <option value="staff">الموظفين والإداريين فقط</option>
            </select>
          </div>

          {(search || periodFilter !== "all" || roleFilter !== "all") && (
            <button
              type="button"
              onClick={() => {
                setSearch("");
                setPeriodFilter("all");
                setRoleFilter("all");
              }}
              className="rounded-xl border border-app-line bg-app-card px-3 py-2 text-xs text-app-muted hover:text-rose-400 hover:border-rose-500/40 transition shrink-0"
            >
              مسح الفلاتر
            </button>
          )}
        </div>

        <Button variant="primary" onClick={openCreateModal} className="flex items-center gap-2 shrink-0">
          <HandCoinsIcon className="size-4" />
          <span>صرف راتب جديد</span>
        </Button>
      </div>

      {/* Table */}
      <div className="rounded-2xl border border-app-line/40 bg-app-panel p-5">
        <div className="mb-4 flex items-center justify-between border-b border-app-line/30 pb-3">
          <div>
            <h2 className="text-lg font-bold text-app-text">سجل مدفوعات ورواتب الكوادر</h2>
            <p className="text-xs text-app-muted">
              حركات صرف الرواتب والعمولات النقدية المسجلة عبر الصناديق
            </p>
          </div>
          <span className="rounded-lg bg-app-card-soft px-3 py-1 text-xs font-mono text-app-muted-light">
            {payments.length} سند صرف
          </span>
        </div>

        {isLoading ? (
          <div className="flex h-64 items-center justify-center">
            <LoadingSpinner />
          </div>
        ) : payments.length === 0 ? (
          <div className="rounded-xl border border-dashed border-app-line/60 p-12 text-center text-app-muted">
            لا توجد سندات صرف رواتب مسجلة في هذا الفرع.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-right text-sm">
              <thead className="border-b border-app-line/40 text-xs text-app-muted">
                <tr>
                  <th className="p-3">اسم المستحق</th>
                  <th className="p-3">الصفة / الدور</th>
                  <th className="p-3">تاريخ الصرف</th>
                  <th className="p-3">الصندوق المنصرف منه</th>
                  <th className="p-3 text-left">المبلغ المصروف</th>
                  <th className="p-3">طريقة الدفع</th>
                  <th className="p-3">البيان / ملاحظات</th>
                  <th className="p-3 text-left">الإجراءات</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-app-line/20 text-app-text text-xs">
                {payments.map((p) => {
                  const staffName =
                    p.staff?.person?.full_name ||
                    `${p.staff?.person?.first_name || ""} ${p.staff?.person?.last_name || ""}`.trim() ||
                    p.staff_name ||
                    `#${p.staff_id}`;

                  return (
                    <tr key={p.id} className="hover:bg-app-card-soft/40 transition">
                      <td className="p-3 font-medium text-app-text">{staffName}</td>
                      <td className="p-3">
                        <span className="rounded-full bg-app-card-soft px-2.5 py-0.5 text-[10px] font-medium text-app-muted-light border border-app-line/30">
                          {p.staff?.role || "كادر"}
                        </span>
                      </td>
                      <td className="p-3 font-mono text-app-muted whitespace-nowrap">{p.date || p.payment_date}</td>
                      <td className="p-3 text-app-muted-light">{p.safe?.name || "صندوق عام"}</td>
                      <td className="p-3 text-left font-mono font-bold text-rose-400">
                        {p.currency === "SYP"
                          ? `${Number(p.amount || 0).toLocaleString()} ل.س`
                          : `$${Number(p.amount || 0).toLocaleString()}`}
                      </td>
                      <td className="p-3 font-mono text-app-muted">{p.payment_method || "نقداً"}</td>
                      <td className="p-3 text-app-muted max-w-xs truncate">{p.notes || "-"}</td>
                      <td className="p-3 text-left">
                        <button
                          onClick={() => setDeletePayment(p)}
                          className="grid size-7 place-items-center rounded-lg border border-app-line/40 bg-app-card-soft text-app-muted-light hover:border-rose-500 hover:text-rose-400 transition"
                          title="حذف السجل"
                        >
                          <TrashIcon className="size-3.5" />
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Payment Form Modal */}
      <SalaryPaymentFormModal
        isOpen={isFormOpen}
        onClose={closeFormModal}
        staffList={staffList}
        safes={safes}
        periods={periods}
        unpaidPayslips={unpaidPayslips}
        onSave={handleSavePayment}
        isLoading={isSaving}
        errors={formErrors}
      />

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        open={Boolean(deletePayment)}
        title="تأكيد حذف سند الراتب"
        message="هل أنت متأكد من رغبتك في حذف سجل صرف هذا الراتب؟"
        requiredConfirmation="delete"
        confirmationValue={deleteConfirmation}
        onConfirmationChange={setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        confirmText="حذف السجل"
        onConfirm={handleDeletePayment}
        onClose={() => setDeletePayment(null)}
        isLoading={isDeleting}
        variant="danger"
      />
    </div>
  );
}

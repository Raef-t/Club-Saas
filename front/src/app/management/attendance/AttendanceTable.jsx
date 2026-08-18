"use client";

import { useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { ATTENDANCE_TABLE_COLUMNS } from "./attendanceConstants";
import AttendanceStatusBadge from "./AttendanceStatusBadge";

const TYPE_OPTIONS = [
  { value: "all", label: "كل الأنواع" },
  { value: "member", label: "الأعضاء" },
  { value: "staff", label: "الموظفون" },
];

function AttendanceConsumptions({ consumptions }) {
  if (!consumptions?.length) return <span className="text-app-muted-light">-</span>;

  return (
    <div className="flex min-w-0 flex-wrap justify-center gap-1.5">
      {consumptions.map((consumption, index) => (
        <span
          key={consumption.id ?? `${consumption.subscriptionPlanId || "plan"}-${index}`}
          title={consumption.subscriptionPlanName}
          className="max-w-full break-words rounded-md bg-app-yellow/15 px-2 py-1 text-[11px] leading-5 text-app-yellow"
        >
          {consumption.subscriptionPlanName}
        </span>
      ))}
    </div>
  );
}

/**
 * Renders attendance history with row-level checkout and rollback actions.
 */
export default function AttendanceTable({
  rows,
  isLoading,
  errorMessage,
  onRetry,
  typeFilter,
  fromDate,
  toDate,
  hasFilters,
  onTypeFilterChange,
  onFromDateChange,
  onToDateChange,
  onResetFilters,
  onCheckOut,
  onRollback,
  isCheckingOut,
  isRollingBack,
}) {
  const [pendingCheckOut, setPendingCheckOut] = useState(null);
  const [pendingRollback, setPendingRollback] = useState(null);
  const actionsDisabled = isCheckingOut || isRollingBack;

  const columns = useMemo(
    () => [
      {
        key: "number",
        label: "الرقم",
        align: "center",
        width: "48px",
        type: "rowNumber",
        sortable: false,
      },
      { key: "type", label: "النوع", align: "center", width: "64px" },
      {
        key: "member",
        label: "العضو / الموظف",
        align: "center",
        width: "minmax(120px,1fr)",
      },
      {
        key: "consumptions",
        label: "الاشتراكات المستخدمة",
        align: "center",
        width: "minmax(180px,1.35fr)",
        sortValue: (row) =>
          row.consumptions.map((consumption) => consumption.subscriptionPlanName).join("، "),
        render: (value) => <AttendanceConsumptions consumptions={value} />,
      },
      {
        key: "checkIn",
        label: "تاريخ ووقت الدخول",
        align: "center",
        width: "150px",
      },
      { key: "checkOut", label: "وقت الانصراف", align: "center", width: "88px" },
      {
        key: "duration",
        label: "المدة",
        align: "center",
        width: "78px",
        render: (value) => value || "-",
      },
      { key: "locker", label: "الخزانة", align: "center", width: "70px" },
      {
        key: "status",
        label: "الحالة",
        align: "center",
        width: "88px",
        render: (value, row) =>
          row.isOpen ? (
            <button
              type="button"
              title="اضغط لتسجيل الانصراف"
              aria-label={`تسجيل انصراف ${row.member}`}
              disabled={actionsDisabled}
              className="rounded-lg outline-none transition focus:ring-2 focus:ring-app-yellow/60 disabled:cursor-not-allowed disabled:opacity-60"
              onClick={() => setPendingCheckOut(row)}
            >
              <AttendanceStatusBadge status={value} interactive />
            </button>
          ) : (
            <AttendanceStatusBadge status={value} />
          ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        width: "74px",
        sortable: false,
        render: (_, row) => (
          <Button
            type="button"
            tone="danger"
            className="h-8 px-3 text-xs"
            disabled={actionsDisabled}
            onClick={() => setPendingRollback(row)}
          >
            تراجع
          </Button>
        ),
      },
    ],
    [actionsDisabled],
  );

  const emptyMessage = errorMessage ? (
    <div className="space-y-3 text-center">
      <p className="text-app-red">{errorMessage}</p>
      <Button type="button" tone="outline" className="h-9 px-3 text-xs" onClick={onRetry}>
        إعادة المحاولة
      </Button>
    </div>
  ) : (
    "لا توجد حركات حضور مسجلة."
  );

  async function confirmCheckOut() {
    if (!pendingCheckOut) return;
    const succeeded = await onCheckOut(pendingCheckOut);
    if (succeeded) setPendingCheckOut(null);
  }

  async function confirmRollback() {
    if (!pendingRollback) return;
    const succeeded = await onRollback(pendingRollback, []);
    if (succeeded) setPendingRollback(null);
  }

  return (
    <>
      <DataTable
        title="سجل الحضور والانصراف"
        columns={columns}
        rows={rows}
        minWidth="0"
        tableColumns={ATTENDANCE_TABLE_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        totalPages={0}
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        getRowKey={(row) => row.id}
        isLoading={isLoading}
        loadingRows={4}
        emptyMessage={emptyMessage}
        toolbarActions={
          <div className="col-span-2 flex w-full flex-wrap items-end gap-2 sm:w-auto">
            <label className="min-w-36 flex-1 text-right text-xs text-app-muted-light sm:flex-none">
              النوع
              <Dropdown
                className="mt-1.5"
                buttonClassName="h-9 bg-app-card-soft"
                value={typeFilter}
                options={TYPE_OPTIONS}
                onChange={onTypeFilterChange}
              />
            </label>
            <div className="min-w-40 flex-1 sm:flex-none">
              <DatePickerSmart
                label="من تاريخ"
                value={fromDate}
                onChange={onFromDateChange}
                placeholder="DD/MM/YYYY"
                compact
              />
            </div>
            <div className="min-w-40 flex-1 sm:flex-none">
              <DatePickerSmart
                label="إلى تاريخ"
                value={toDate}
                onChange={onToDateChange}
                placeholder="DD/MM/YYYY"
                compact
              />
            </div>
            {hasFilters && (
              <Button
                type="button"
                tone="ghost"
                className="h-9 px-3 text-xs"
                onClick={onResetFilters}
              >
                مسح الفلاتر
              </Button>
            )}
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            الإجمالي:{" "}
            <span className="font-medium text-app-text">
              {rows.length.toLocaleString("ar")} حركة
            </span>
          </p>
        }
      />

      <ConfirmDialog
        open={Boolean(pendingCheckOut)}
        onClose={() => setPendingCheckOut(null)}
        onConfirm={confirmCheckOut}
        title="تسجيل الانصراف"
        message={`هل تريد تسجيل انصراف ${pendingCheckOut?.member || ""}?${
          pendingCheckOut?.lockerNumber
            ? ` سيتم فك حجز الخزانة ${pendingCheckOut.lockerNumber} تلقائياً.`
            : ""
        }`}
        confirmLabel="تسجيل الانصراف"
        tone="primary"
        isLoading={isCheckingOut}
      />

      <ConfirmDialog
        open={Boolean(pendingRollback)}
        onClose={() => setPendingRollback(null)}
        onConfirm={confirmRollback}
        title="التراجع عن الجلسة"
        message={`سيتم التراجع عن جلسة ${pendingRollback?.member || ""} وإرجاعها${
          pendingRollback?.lockerNumber ? ` وفك حجز الخزانة ${pendingRollback.lockerNumber}` : ""
        }. هل تريد المتابعة؟`}
        confirmLabel="تأكيد التراجع"
        isLoading={isRollingBack}
      />
    </>
  );
}

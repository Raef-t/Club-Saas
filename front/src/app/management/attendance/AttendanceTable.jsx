"use client";

import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { ATTENDANCE_TABLE_COLUMNS } from "./attendanceConstants";
import AttendanceStatusBadge from "./AttendanceStatusBadge";

const TABLE_COLUMNS = [
  { key: "number", label: "الرقم", align: "center", width: "72px" },
  { key: "type", label: "النوع", align: "center", width: "80px" },
  {
    key: "member",
    label: "العضو / الموظف",
    align: "center",
    width: "minmax(150px,1fr)",
  },
  { key: "checkIn", label: "وقت الدخول", align: "center", width: "110px" },
  { key: "checkOut", label: "وقت الانصراف", align: "center", width: "110px" },
  {
    key: "duration",
    label: "المدة",
    align: "center",
    width: "96px",
    render: (value) => (value !== null && value !== undefined ? `${value} دقيقة` : "-"),
  },
  {
    key: "status",
    label: "الحالة",
    align: "center",
    width: "96px",
    render: (value) => <AttendanceStatusBadge status={value} />,
  },
];

const TYPE_OPTIONS = [
  { value: "all", label: "كل الأنواع" },
  { value: "member", label: "الأعضاء" },
  { value: "staff", label: "الموظفون" },
];

/**
 * Renders the attendance history returned for the scanned member.
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
}) {
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

  return (
    <DataTable
      title="سجل الحضور والانصراف"
      columns={TABLE_COLUMNS}
      rows={rows}
      minWidth="900px"
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
          <span className="font-medium text-app-text">{rows.length.toLocaleString("ar")} حركة</span>
        </p>
      }
    />
  );
}

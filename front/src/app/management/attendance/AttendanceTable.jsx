"use client";

import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import { ATTENDANCE_TABLE_COLUMNS } from "./attendanceConstants";
import AttendanceStatusBadge from "./AttendanceStatusBadge";

const TABLE_COLUMNS = [
  { key: "number", label: "الرقم", align: "center", width: "72px" },
  { key: "time", label: "الوقت", align: "center", width: "120px" },
  {
    key: "member",
    label: "العضو",
    align: "center",
    width: "minmax(150px,1fr)",
  },
  {
    key: "activity",
    label: "النشاط",
    align: "center",
    width: "minmax(130px,1fr)",
  },
  {
    key: "coach",
    label: "المدرب",
    align: "center",
    width: "minmax(130px,1fr)",
  },
  { key: "locker", label: "الخزانة", align: "center", width: "96px" },
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

/**
 * Renders the attendance history returned for the scanned member.
 */
export default function AttendanceTable({
  rows,
  hasScannedMember,
  isLoading,
  errorMessage,
  onRetry,
}) {
  const emptyMessage = errorMessage ? (
    <div className="space-y-3 text-center">
      <p className="text-app-red">{errorMessage}</p>
      <Button type="button" tone="outline" className="h-9 px-3 text-xs" onClick={onRetry}>
        إعادة المحاولة
      </Button>
    </div>
  ) : hasScannedMember ? (
    "لا توجد حركات حضور مسجلة لهذا العضو."
  ) : (
    "امسح بطاقة عضو لعرض سجل حضوره."
  );

  return (
    <DataTable
      title={hasScannedMember ? "سجل حضور اللاعب" : "سجل الحضور"}
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
      toolbarMeta={
        <p className="text-sm text-app-muted-light">
          الإجمالي:{" "}
          <span className="font-medium text-app-text">{rows.length.toLocaleString("ar")} حركة</span>
        </p>
      }
    />
  );
}

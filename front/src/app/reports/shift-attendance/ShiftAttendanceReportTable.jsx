"use client";

import DataTable from "@/components/ui/DataTable";
import Button from "@/components/ui/Button";
import { EyeIcon } from "@/components/icons/Icons";

function CrowdCell({ percentage }) {
  const cleanPercentage = Math.min(Math.max(percentage, 0), 100);
  const toneClass =
    cleanPercentage >= 80
      ? "text-app-red"
      : cleanPercentage >= 40
        ? "text-app-yellow"
        : "text-app-green";

  const barColor =
    cleanPercentage >= 80
      ? "bg-app-red"
      : cleanPercentage >= 40
        ? "bg-app-yellow"
        : "bg-app-green";

  return (
    <div className="flex items-center gap-2.5 min-w-[120px]">
      <div className="h-1.5 w-16 overflow-hidden rounded-full bg-black/40">
        <div
          className={`h-full rounded-full transition-all duration-300 ${barColor}`}
          style={{ width: `${cleanPercentage}%` }}
        />
      </div>
      <span className={`text-xs font-bold ${toneClass}`}>
        {percentage.toLocaleString("ar", { maximumFractionDigits: 1 })}%
      </span>
    </div>
  );
}

export default function ShiftAttendanceReportTable({
  rows,
  summary,
  isLoading,
  onSelectRecord,
}) {
  const columns = [
    {
      key: "shiftName",
      label: "اسم الوردية",
      width: "minmax(120px, 1.3fr)",
    },
    {
      key: "branchName",
      label: "الفرع",
      width: "minmax(100px, 1fr)",
    },
    {
      key: "timeFormatted",
      label: "توقيت الوردية",
      width: "minmax(110px, 1fr)",
      render: (value) => (
        <span className="font-mono text-xs text-app-muted-light" dir="ltr">
          {value}
        </span>
      ),
    },
    {
      key: "attendedPlayersCount",
      label: "مرات الحضور",
      width: "minmax(90px, 0.8fr)",
      render: (value) => (
        <span className="font-semibold text-app-text">
          {value.toLocaleString("ar")}
        </span>
      ),
    },
    {
      key: "uniquePlayersCount",
      label: "اللاعبين الفريدين",
      width: "minmax(100px, 0.8fr)",
      render: (value) => (
        <span className="text-xs font-medium text-app-blue">
          {value.toLocaleString("ar")}
        </span>
      ),
    },
    {
      key: "crowdPercentage",
      label: "معدل الازدحام",
      width: "minmax(120px, 1fr)",
      render: (value) => <CrowdCell percentage={value} />,
    },
    {
      key: "actions",
      label: "الإجراءات",
      width: "95px",
      sortable: false,
      render: (_, row) => (
        <Button
          type="button"
          tone="outline"
          size="sm"
          icon={<EyeIcon className="size-3.5" />}
          onClick={() => onSelectRecord(row)}
          className="h-8 px-2.5 text-xs"
        >
          التفاصيل
        </Button>
      ),
    },
  ];

  return (
    <DataTable
      title="سجلات حضور وازدحام الورديات"
      subtitle={`${rows.length.toLocaleString("ar")} وردية مطابقة للفلاتر • ${summary.period_label}`}
      columns={columns}
      rows={rows}
      getRowKey={(row) => row.id}
      showAdd={false}
      showSearch={false}
      showFilter={false}
      showExport={false}
      isLoading={isLoading}
      pageSize={10}
      pageSizeOptions={[10, 20, 50]}
      emptyMessage="لا توجد بيانات حضور للورديات مطابقة لمعايير البحث."
    />
  );
}

"use client";

import DataTable from "@/components/ui/DataTable";

function formatTime(value) {
  if (!value || value === "-") return "-";
  return String(value).slice(0, 5);
}

function UtilizationCell({ value }) {
  const percentage = Number(value) || 0;
  const tone =
    percentage >= 100 ? "bg-app-red" : percentage >= 80 ? "bg-[#d99300]" : "bg-app-green";

  return (
    <div className="min-w-24" dir="ltr">
      <div className="mb-1 flex items-center justify-between text-xs text-app-muted-light">
        <span>{percentage}%</span>
      </div>
      <div className="h-1.5 overflow-hidden rounded-full bg-app-line">
        <span
          className={`block h-full rounded-full ${tone}`}
          style={{ width: `${Math.min(100, percentage)}%` }}
        />
      </div>
    </div>
  );
}

export default function TimeCapacityReportTable({ rows, isLoading }) {
  const columns = [
    { key: "activityName", label: "النشاط", width: "minmax(160px,1.2fr)" },
    { key: "coachName", label: "الكوتش", width: "minmax(150px,1fr)" },
    { key: "planName", label: "الخطة", width: "minmax(170px,1.25fr)" },
    { key: "dayOfWeek", label: "اليوم", width: "100px" },
    { key: "startTime", label: "وقت البداية", width: "110px", render: formatTime },
    { key: "endTime", label: "وقت النهاية", width: "110px", render: formatTime },
    { key: "capacity", label: "السعة", width: "90px", align: "center" },
    {
      key: "activeSubscribers",
      label: "المشتركون الفعالون",
      width: "130px",
      align: "center",
    },
    {
      key: "remainingCapacity",
      label: "الأماكن المتبقية",
      width: "120px",
      align: "center",
      render: (value) => (
        <span className={`font-medium ${Number(value) > 0 ? "text-app-green" : "text-app-red"}`}>
          {Number(value).toLocaleString("ar")}
        </span>
      ),
    },
    {
      key: "utilization",
      label: "نسبة الإشغال",
      width: "125px",
      render: (value) => <UtilizationCell value={value} />,
    },
    {
      key: "capacityStatusLabel",
      label: "الحالة",
      width: "100px",
      sortable: false,
      render: (value, row) => (
        <span
          className={`inline-flex rounded-md px-2.5 py-1 text-xs font-medium ${row.capacityStatus === "full" ? "status-danger" : "status-success"}`}
        >
          {value}
        </span>
      ),
    },
  ];

  return (
    <DataTable
      title="تفاصيل سعة الحصص"
      subtitle={`${rows.length.toLocaleString("ar")} توقيت مطابق للفلاتر الحالية`}
      columns={columns}
      rows={rows}
      getRowKey={(row) => row.id}
      showAdd={false}
      showSearch={false}
      showFilter={false}
      showExport={false}
      isLoading={isLoading}
      pageSize={10}
      pageSizeOptions={[10, 20, 50, 100]}
      minWidth="1380px"
      desktopScrollable
      emptyMessage="لا توجد حصص أو خطط مطابقة للفلاتر المختارة."
    />
  );
}

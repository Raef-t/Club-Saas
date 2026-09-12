"use client";

import DataTable from "@/components/ui/DataTable";

export default function CoachSubscriptionsReportTable({ rows, isLoading }) {
  const columns = [
    {
      key: "coachName",
      label: "اسم الكوتش",
      width: "minmax(180px,1.2fr)",
      render: (value) => <span className="font-medium text-app-text">{value}</span>,
    },
    {
      key: "activities",
      label: "الأنشطة",
      width: "minmax(260px,2fr)",
      sortable: false,
      render: (activities) =>
        activities.length ? (
          <div className="flex flex-wrap gap-1.5">
            {activities.map((activity) => (
              <span
                key={activity}
                className="inline-flex rounded-md border border-app-yellow/20 bg-app-yellow-soft px-2.5 py-1 text-xs text-app-yellow"
              >
                {activity}
              </span>
            ))}
          </div>
        ) : (
          <span className="text-app-muted">-</span>
        ),
    },
    {
      key: "activitiesCount",
      label: "عدد الأنشطة",
      width: "120px",
      align: "center",
      render: (value) => Number(value).toLocaleString("ar"),
    },
    {
      key: "activePlayersCount",
      label: "اللاعبون الفعالون",
      width: "150px",
      align: "center",
      render: (value) => (
        <span className="font-medium text-app-green">{Number(value).toLocaleString("ar")}</span>
      ),
    },
  ];

  return (
    <DataTable
      title="كوتشات الحصص الجماعية"
      subtitle={`${rows.length.toLocaleString("ar")} كوتش ضمن النطاق الحالي`}
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
      minWidth="760px"
      desktopScrollable
      emptyMessage="لا توجد بيانات لكوتشات الحصص الجماعية ضمن الفرع المختار."
    />
  );
}

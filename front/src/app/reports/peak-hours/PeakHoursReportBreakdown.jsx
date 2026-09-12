"use client";

import BarChart from "@/components/charts/BarChart";
import DataTable from "@/components/ui/DataTable";
import SectionCard from "@/components/ui/SectionCard";
import { getHolidayLabel } from "./peakHoursReportUtils";

function HourRanking({ title, items, tone }) {
  return (
    <div className="rounded-xl border border-app-line bg-app-card-soft px-4 py-4">
      <h3 className="text-sm font-medium text-app-text">{title}</h3>
      {items.length > 0 ? (
        <div className="mt-3 flex flex-wrap gap-2">
          {items.map((item) => (
            <span
              key={`${item.hour}-${item.label}`}
              className={`inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs ${tone}`}
            >
              <strong dir="ltr">{item.label}</strong>
              <span>{item.count.toLocaleString("ar")} دخول</span>
            </span>
          ))}
        </div>
      ) : (
        <p className="mt-3 text-xs text-app-muted">لا توجد بيانات كافية.</p>
      )}
    </div>
  );
}

export default function PeakHoursReportBreakdown({ report, isLoading }) {
  const dailyColumns = [
    { key: "dayName", label: "اليوم", width: "1fr" },
    {
      key: "totalCheckIns",
      label: "إجمالي تسجيلات الدخول",
      width: "1fr",
      align: "center",
    },
    {
      key: "isWeeklyHoliday",
      label: "حالة اليوم",
      width: "1fr",
      align: "center",
      sortable: false,
      render: (value) => (
        <span
          className={`inline-flex rounded-md px-2.5 py-1 text-xs font-medium ${value ? "status-warning" : "status-success"}`}
        >
          {value ? "عطلة أسبوعية" : "يوم عمل"}
        </span>
      ),
    },
  ];
  const hourlyChartData = report.hourlyBreakdown.map((item) => ({
    label: item.label,
    value: item.count,
  }));
  const dailyChartData = report.dailyBreakdown.map((item) => ({
    label: item.dayName,
    value: item.totalCheckIns,
  }));

  return (
    <div className="space-y-5">
      <section className="grid gap-4 xl:grid-cols-2">
        <HourRanking
          title="أعلى ساعات الذروة"
          items={report.topPeakHours}
          tone="bg-app-red/10 text-app-red"
        />
        <HourRanking
          title="أهدأ ساعات الحركة"
          items={report.topOffPeakHours}
          tone="bg-app-green/10 text-app-green"
        />
      </section>

      <SectionCard
        title="الحركة على مدار اليوم"
        subtitle="عدد تسجيلات الدخول موزعة على ساعات اليوم الأربع والعشرين."
        contentClassName="border-t border-app-line px-3 py-4"
      >
        <div>
          <BarChart data={hourlyChartData} height={240} />
        </div>
      </SectionCard>

      <div className="grid gap-5 2xl:grid-cols-[1.1fr_1fr]">
        <SectionCard
          title="الحضور حسب أيام الأسبوع"
          subtitle="مقارنة الحركة بين أيام الأسبوع بعد استبعاد العطل المحددة."
          contentClassName="border-t border-app-line px-3 py-4"
        >
          <BarChart data={dailyChartData} height={220} />
        </SectionCard>

        <DataTable
          title="التوزيع اليومي"
          columns={dailyColumns}
          rows={report.dailyBreakdown}
          getRowKey={(row) => row.id}
          showAdd={false}
          showSearch={false}
          showFilter={false}
          showExport={false}
          pagination={false}
          sortable={false}
          isLoading={isLoading}
          emptyMessage="لا توجد بيانات يومية ضمن الفترة المختارة."
        />
      </div>

      {report.specificHolidays.length > 0 && (
        <SectionCard
          title="العطل المحددة المستبعدة"
          subtitle="هذه الأيام لم تدخل في حساب الذروة والانخفاض."
          contentClassName="border-t border-app-line p-4"
        >
          <div className="flex flex-wrap gap-2">
            {report.specificHolidays.map((holiday, index) => (
              <span
                key={holiday?.id || holiday?.date || index}
                className="rounded-lg bg-app-yellow-soft px-3 py-2 text-xs text-app-yellow"
              >
                {getHolidayLabel(holiday)}
              </span>
            ))}
          </div>
        </SectionCard>
      )}
    </div>
  );
}

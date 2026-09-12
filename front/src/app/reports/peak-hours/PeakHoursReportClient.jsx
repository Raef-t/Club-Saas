"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { printReports } from "../dashboard/reportPrint";
import PeakHoursReportBreakdown from "./PeakHoursReportBreakdown";
import PeakHoursReportFilters from "./PeakHoursReportFilters";
import PeakHoursReportSummary from "./PeakHoursReportSummary";
import { usePeakHoursReport } from "./usePeakHoursReport";

function createPrintableReports(report) {
  const metrics = [
    {
      label: "الحضور المحلل",
      value: report.summary.total_attendances_analyzed.toLocaleString("ar"),
    },
    { label: "أكثر الأيام ازدحاماً", value: report.summary.busiest_day },
    { label: "أهدأ الأيام", value: report.summary.quietest_day },
    { label: "وقت الذروة", value: report.summary.peak_hours_range },
    { label: "الوقت الهادئ", value: report.summary.off_peak_hours_range },
  ];

  return [
    {
      id: "peak-hours-hourly",
      title: "توزيع الحضور حسب الساعة",
      description: "عدد تسجيلات الدخول لكل ساعة خلال الفترة المختارة.",
      metrics,
      columns: [
        { key: "label", label: "الساعة" },
        { key: "count", label: "عدد تسجيلات الدخول" },
      ],
      rows: report.hourlyBreakdown,
      emptyMessage: "لا توجد بيانات ساعية ضمن الفترة المختارة.",
    },
    {
      id: "peak-hours-daily",
      title: "توزيع الحضور حسب اليوم",
      description: "إجمالي تسجيلات الدخول بحسب أيام الأسبوع.",
      metrics,
      columns: [
        { key: "dayName", label: "اليوم" },
        { key: "totalCheckIns", label: "إجمالي الدخول" },
        { key: "holidayLabel", label: "حالة اليوم" },
      ],
      rows: report.dailyBreakdown.map((row) => ({
        ...row,
        holidayLabel: row.isWeeklyHoliday ? "عطلة أسبوعية" : "يوم عمل",
      })),
      emptyMessage: "لا توجد بيانات يومية ضمن الفترة المختارة.",
    },
  ];
}

export default function PeakHoursReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = usePeakHoursReport();

  function handlePrint() {
    const opened = printReports(
      createPrintableReports(reportData.report),
      reportData.branchName,
      brandLogoUrl,
    );
    if (!opened) toast.warning("اسمح بالنوافذ المنبثقة حتى تتمكن من طباعة التقرير.");
  }

  if (reportData.isLoading) {
    return (
      <SkeletonPage
        blocks={[
          { type: "header", actions: 2 },
          { type: "stats", count: 6 },
          { type: "custom", className: "h-72 rounded-2xl" },
          { type: "table", rows: 7, columns: 3 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="تقرير ساعات الذروة والانخفاض"
      description="تحليل حركة الحضور حسب الساعة واليوم خلال الفترة المختارة."
      branchName={reportData.branchName}
      isRefreshing={reportData.isFetching}
      onRefresh={reportData.refresh}
      onPrint={handlePrint}
    >
      {reportData.error && (
        <div
          className="rounded-xl border border-app-red/30 bg-app-red/10 px-4 py-3 text-sm text-app-red"
          role="alert"
        >
          {getApiErrorMessage(reportData.error, "تعذر تحميل تقرير ساعات الذروة. حاول مرة أخرى.")}
        </div>
      )}

      <PeakHoursReportFilters
        filters={reportData.draftFilters}
        validationError={reportData.validationError}
        isFetching={reportData.isFetching}
        onChange={reportData.updateFilter}
        onApply={reportData.applyFilters}
        onReset={reportData.resetFilters}
      />
      <PeakHoursReportSummary summary={reportData.report.summary} />
      <PeakHoursReportBreakdown report={reportData.report} isLoading={reportData.isFetching} />
    </ReportPageShell>
  );
}

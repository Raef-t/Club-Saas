"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { printReports } from "../dashboard/reportPrint";
import TimeCapacityReportFilters from "./TimeCapacityReportFilters";
import TimeCapacityReportSummary from "./TimeCapacityReportSummary";
import TimeCapacityReportTable from "./TimeCapacityReportTable";
import { useTimeCapacityReport } from "./useTimeCapacityReport";

function createPrintableReport(rows, summary) {
  return {
    id: "time-capacity",
    title: "تقرير سعة الحصص حسب الوقت",
    description: "سعة الحصص والخطط والمشتركين الفعالين مجمعة حسب الأنشطة والكوتشات.",
    metrics: [
      { label: "الأنشطة", value: summary.total_activities.toLocaleString("ar") },
      { label: "الكوتشات", value: summary.total_coaches.toLocaleString("ar") },
      { label: "الخطط", value: summary.total_plans.toLocaleString("ar") },
      {
        label: "المشتركون الفعالون",
        value: summary.total_active_subscribers.toLocaleString("ar"),
      },
    ],
    columns: [
      { key: "activityName", label: "النشاط" },
      { key: "coachName", label: "الكوتش" },
      { key: "planName", label: "الخطة" },
      { key: "dayOfWeek", label: "اليوم" },
      { key: "timeRange", label: "الوقت" },
      { key: "capacity", label: "السعة" },
      { key: "activeSubscribers", label: "المشتركون" },
      { key: "remainingCapacity", label: "المتبقي" },
      { key: "utilizationLabel", label: "الإشغال" },
      { key: "capacityStatusLabel", label: "الحالة" },
    ],
    rows: rows.map((row) => ({
      ...row,
      timeRange: `${String(row.startTime).slice(0, 5)} - ${String(row.endTime).slice(0, 5)}`,
      utilizationLabel: `${row.utilization}%`,
    })),
    emptyMessage: "لا توجد حصص أو خطط مطابقة للفلاتر المختارة.",
  };
}

export default function TimeCapacityReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useTimeCapacityReport();

  function handlePrint() {
    const opened = printReports(
      [createPrintableReport(reportData.rows, reportData.report.summary)],
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
          { type: "stats", count: 4 },
          { type: "table", rows: 7, columns: 8 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="تقرير سعة الحصص حسب الوقت"
      description="عرض السعة والإشغال حسب النشاط والكوتش والخطة والفترة الزمنية."
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
          {getApiErrorMessage(reportData.error, "تعذر تحميل تقرير سعة الحصص. حاول مرة أخرى.")}
        </div>
      )}

      <TimeCapacityReportFilters
        filters={reportData.draftFilters}
        activities={reportData.activities}
        plans={reportData.plans}
        validationError={reportData.validationError}
        isFetching={reportData.isFetching}
        onChange={reportData.updateFilter}
        onApply={reportData.applyFilters}
        onReset={reportData.resetFilters}
      />

      <TimeCapacityReportSummary summary={reportData.report.summary} />
      <TimeCapacityReportTable rows={reportData.rows} isLoading={reportData.isFetching} />
    </ReportPageShell>
  );
}

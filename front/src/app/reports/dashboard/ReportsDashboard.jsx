"use client";

import { useState } from "react";
import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { printReports } from "./reportPrint";
import { ReportCards, ReportPanel } from "./ReportsDashboardWidgets";
import { useOperationalReports } from "./useOperationalReports";

/**
 * Composes live branch-aware reports and their print controls.
 */
export default function ReportsDashboard({ initialData }) {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useOperationalReports({ initialData });
  const [selectedReportId, setSelectedReportId] = useState("hall");
  const selectedReport =
    reportData.reports.find((report) => report.id === selectedReportId) || reportData.reports[0];

  /**
   * Prints one report and warns when the browser blocks the print window.
   */
  function handlePrintReport(report) {
    const opened = printReports([report], reportData.branchName, brandLogoUrl);
    if (!opened) {
      toast.warning("اسمح بالنوافذ المنبثقة حتى تتمكن من طباعة التقرير.");
    }
  }

  /**
   * Prints every available report on a separate page.
   */
  function handlePrintAll() {
    const opened = printReports(reportData.reports, reportData.branchName, brandLogoUrl);
    if (!opened) {
      toast.warning("اسمح بالنوافذ المنبثقة حتى تتمكن من طباعة التقارير.");
    }
  }

  if (reportData.isLoading) {
    return (
      <SkeletonPage
        blocks={[
          { type: "header", actions: 2 },
          { type: "stats", count: 6 },
          { type: "table", rows: 6, columns: 6 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="التقارير التشغيلية"
      description="لوحة موحّدة لمراقبة المؤشرات التشغيلية واختيار التقرير المطلوب وعرضه أو طباعته."
      branchName={reportData.branchName}
      isRefreshing={reportData.isRefreshing}
      onRefresh={reportData.refresh}
      onPrint={handlePrintAll}
      printLabel="طباعة جميع التقارير"
    >
      {reportData.hasError && (
        <div
          className="rounded-xl border border-app-yellow/30 bg-app-yellow/10 px-4 py-3 text-sm text-app-muted-light"
          role="alert"
        >
          تعذر تحديث بعض مصادر البيانات. تم إبقاء آخر بيانات متاحة، ويمكنك إعادة المحاولة من زر
          التحديث.
          {reportData.hasAttendanceError && (
            <p className="mt-1 text-xs text-app-yellow">
              تقرير الموجودين داخل الصالة يحتاج أن يسمح Backend بجلب سجل الحضور العام.
            </p>
          )}
        </div>
      )}

      <StatsGrid items={reportData.stats} />

      <ReportCards
        reports={reportData.reports}
        selectedReportId={selectedReport.id}
        onSelect={setSelectedReportId}
      />

      <ReportPanel
        report={selectedReport}
        branchName={reportData.branchName}
        onPrint={() => handlePrintReport(selectedReport)}
      />
    </ReportPageShell>
  );
}

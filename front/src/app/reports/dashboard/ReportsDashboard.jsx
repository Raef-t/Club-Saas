"use client";

import { useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import { PrintIcon } from "@/components/icons/Icons";
import { useToast } from "@/components/ui/Toast";
import { printReports } from "./reportPrint";
import { ReportCards, ReportPanel } from "./ReportsDashboardWidgets";
import { useOperationalReports } from "./useOperationalReports";

/**
 * Composes live branch-aware reports and their print controls.
 */
export default function ReportsDashboard({ initialData }) {
  const toast = useToast();
  const reportData = useOperationalReports({ initialData });
  const [selectedReportId, setSelectedReportId] = useState("hall");
  const selectedReport =
    reportData.reports.find((report) => report.id === selectedReportId) || reportData.reports[0];

  /**
   * Prints one report and warns when the browser blocks the print window.
   */
  function handlePrintReport(report) {
    const opened = printReports([report], reportData.branchName);
    if (!opened) {
      toast.warning("اسمح بالنوافذ المنبثقة حتى تتمكن من طباعة التقرير.");
    }
  }

  /**
   * Prints every available report on a separate page.
   */
  function handlePrintAll() {
    const opened = printReports(reportData.reports, reportData.branchName);
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
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="نظام التقارير"
        title="التقارير التشغيلية"
        subtitle={`بيانات مباشرة للفرع: ${reportData.branchName}. اختر التقرير المطلوب ثم اطبعه منفرداً أو اطبع جميع التقارير.`}
        action={
          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              tone="outline"
              loading={reportData.isRefreshing}
              onClick={reportData.refresh}
            >
              تحديث البيانات
            </Button>
            <Button type="button" icon={<PrintIcon className="size-4" />} onClick={handlePrintAll}>
              طباعة جميع التقارير
            </Button>
          </div>
        }
      />

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
    </div>
  );
}

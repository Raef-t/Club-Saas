"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { printReports } from "../dashboard/reportPrint";
import { useShiftAttendanceReport } from "./useShiftAttendanceReport";
import { createPrintableShiftAttendanceReport } from "./shiftAttendanceReportUtils";
import ShiftAttendanceReportFilters from "./ShiftAttendanceReportFilters";
import ShiftAttendanceReportSummary from "./ShiftAttendanceReportSummary";
import ShiftAttendanceReportTable from "./ShiftAttendanceReportTable";
import ShiftAttendanceDetailsModal from "./ShiftAttendanceDetailsModal";

export default function ShiftAttendanceReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useShiftAttendanceReport();

  function handlePrint() {
    const printableReport = createPrintableShiftAttendanceReport(
      reportData.rows,
      reportData.summary,
    );
    const opened = printReports([printableReport], reportData.branchName, brandLogoUrl);

    if (!opened) {
      toast.warning("يرجى السماح بالنوافذ المنبثقة لطباعة التقرير.");
    }
  }

  if (reportData.isLoading) {
    return (
      <SkeletonPage
        blocks={[
          { type: "header", actions: 2 },
          { type: "stats", count: 3 },
          { type: "table", rows: 6, columns: 6 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="تقرير حضور وازدحام ورديات الأنشطة"
      description="متابعة كثافة حضور اللاعبين في الورديات وتحديد أوقات الذروة والهدوء."
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
          {getApiErrorMessage(
            reportData.error,
            "تعذر تحميل تقرير حضور الورديات. يرجى المحاولة مرة أخرى.",
          )}
        </div>
      )}

      <ShiftAttendanceReportFilters
        filters={reportData.draftFilters}
        activities={reportData.activities}
        shifts={reportData.shifts}
        validationError={reportData.validationError}
        isFetching={reportData.isFetching}
        onChange={reportData.updateFilter}
        onApply={reportData.applyFilters}
        onReset={reportData.resetFilters}
      />

      <ShiftAttendanceReportSummary summary={reportData.summary} />

      <ShiftAttendanceReportTable
        rows={reportData.rows}
        summary={reportData.summary}
        isLoading={reportData.isFetching}
        onSelectRecord={reportData.setSelectedRecord}
      />

      <ShiftAttendanceDetailsModal
        record={reportData.selectedRecord}
        onClose={() => reportData.setSelectedRecord(null)}
      />
    </ReportPageShell>
  );
}

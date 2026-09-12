"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { printReports } from "../dashboard/reportPrint";
import { useFrozenTerminatedReport } from "./useFrozenTerminatedReport";
import { createPrintableFrozenTerminatedReport } from "./frozenTerminatedReportUtils";
import FrozenTerminatedReportFilters from "./FrozenTerminatedReportFilters";
import FrozenTerminatedReportSummary from "./FrozenTerminatedReportSummary";
import FrozenTerminatedReportTable from "./FrozenTerminatedReportTable";
import FrozenTerminatedDetailsModal from "./FrozenTerminatedDetailsModal";

export default function FrozenTerminatedReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useFrozenTerminatedReport();

  function handlePrint() {
    const printableReport = createPrintableFrozenTerminatedReport(
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
          { type: "table", rows: 7, columns: 8 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="تقرير الاشتراكات المجمدة والملغاة"
      description="متابعة الاشتراكات المجمدة والملغاة وأسباب التوقف والإيرادات المعلقة."
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
            "تعذر تحميل تقرير الاشتراكات المجمدة والملغاة. يرجى المحاولة مرة أخرى.",
          )}
        </div>
      )}

      <FrozenTerminatedReportFilters
        filters={reportData.draftFilters}
        plans={reportData.plans}
        validationError={reportData.validationError}
        isFetching={reportData.isFetching}
        onChange={reportData.updateFilter}
        onApply={reportData.applyFilters}
        onReset={reportData.resetFilters}
      />

      <FrozenTerminatedReportSummary summary={reportData.summary} />

      <FrozenTerminatedReportTable
        rows={reportData.rows}
        summary={reportData.summary}
        isLoading={reportData.isFetching}
        onSelectRecord={reportData.setSelectedRecord}
      />

      <FrozenTerminatedDetailsModal
        record={reportData.selectedRecord}
        onClose={() => reportData.setSelectedRecord(null)}
      />
    </ReportPageShell>
  );
}

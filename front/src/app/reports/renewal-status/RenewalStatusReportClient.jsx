"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { printReports } from "../dashboard/reportPrint";
import { useRenewalStatusReport } from "./useRenewalStatusReport";
import { createPrintableRenewalReport } from "./renewalStatusReportUtils";
import RenewalStatusReportFilters from "./RenewalStatusReportFilters";
import RenewalStatusReportSummary from "./RenewalStatusReportSummary";
import RenewalStatusReportTable from "./RenewalStatusReportTable";
import RenewalDetailsModal from "./RenewalDetailsModal";

export default function RenewalStatusReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useRenewalStatusReport();

  function handlePrint() {
    const printableReport = createPrintableRenewalReport(reportData.rows, reportData.summary);
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
          { type: "stats", count: 4 },
          { type: "table", rows: 7, columns: 8 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="تقرير حالة تجديد الاشتراكات"
      description="متابعة الاشتراكات المنتهية ومعدلات التجديد والإيرادات المحتملة."
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
            "تعذر تحميل تقرير حالة تجديد الاشتراكات. يرجى المحاولة مرة أخرى.",
          )}
        </div>
      )}

      <RenewalStatusReportFilters
        filters={reportData.draftFilters}
        plans={reportData.plans}
        coaches={reportData.coaches}
        validationError={reportData.validationError}
        isFetching={reportData.isFetching}
        onChange={reportData.updateFilter}
        onApply={reportData.applyFilters}
        onReset={reportData.resetFilters}
      />

      <RenewalStatusReportSummary summary={reportData.summary} />

      <RenewalStatusReportTable
        rows={reportData.rows}
        summary={reportData.summary}
        isLoading={reportData.isFetching}
        onSelectRecord={reportData.setSelectedRecord}
      />

      <RenewalDetailsModal
        record={reportData.selectedRecord}
        onClose={() => reportData.setSelectedRecord(null)}
      />
    </ReportPageShell>
  );
}

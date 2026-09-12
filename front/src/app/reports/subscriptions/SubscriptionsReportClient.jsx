"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { formatDate, formatMoney } from "@/lib/utils";
import { printReports } from "../dashboard/reportPrint";
import SubscriptionsReportFilters from "./SubscriptionsReportFilters";
import SubscriptionsReportSummary from "./SubscriptionsReportSummary";
import SubscriptionsReportTable from "./SubscriptionsReportTable";
import { useSubscriptionsReport } from "./useSubscriptionsReport";

function createPrintableReport(rows, summary) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;

  return {
    id: "subscriptions-comprehensive",
    title: "التقرير الشامل للاشتراكات",
    description: "تفاصيل الاشتراكات والمبالغ المالية وحالتي الاشتراك والدفع وفق الفلاتر المختارة.",
    metrics: [
      { label: "إجمالي الاشتراكات", value: summary.total_subscriptions.toLocaleString("ar") },
      { label: "إجمالي الإيراد", value: formatMoney(summary.total_revenue, currency) },
      { label: "المدفوع", value: formatMoney(summary.total_paid, currency) },
      { label: "المتبقي", value: formatMoney(summary.total_remaining, currency) },
      { label: "الفعال", value: summary.active_count.toLocaleString("ar") },
      { label: "المنتهي", value: summary.finished_count.toLocaleString("ar") },
      { label: "المجمّد", value: summary.frozen_count.toLocaleString("ar") },
      { label: "الملغى", value: summary.terminated_count.toLocaleString("ar") },
    ],
    columns: [
      { key: "membershipNumber", label: "رقم العضوية" },
      { key: "memberName", label: "اللاعب" },
      { key: "planName", label: "الخطة" },
      { key: "coachName", label: "الكوتش" },
      { key: "startDate", label: "البداية" },
      { key: "endDate", label: "النهاية" },
      { key: "totalAmount", label: "القيمة" },
      { key: "paidAmount", label: "المدفوع" },
      { key: "remainingAmount", label: "المتبقي" },
      { key: "statusLabel", label: "حالة الاشتراك" },
      { key: "paymentStatusLabel", label: "حالة الدفع" },
    ],
    rows: rows.map((row) => ({
      ...row,
      startDate: formatDate(row.startDate),
      endDate: formatDate(row.endDate),
      totalAmount: formatMoney(row.totalAmount, currency),
      paidAmount: formatMoney(row.paidAmount, currency),
      remainingAmount: formatMoney(row.remainingAmount, currency),
    })),
    emptyMessage: "لا توجد اشتراكات مطابقة للفلاتر المختارة.",
  };
}

export default function SubscriptionsReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useSubscriptionsReport();

  function handlePrint() {
    const printableReport = createPrintableReport(reportData.rows, reportData.report.summary);
    const opened = printReports([printableReport], reportData.branchName, brandLogoUrl);

    if (!opened) {
      toast.warning("اسمح بالنوافذ المنبثقة حتى تتمكن من طباعة التقرير.");
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
      title="التقرير الشامل للاشتراكات"
      description="ملخص مالي وتفصيلي للاشتراكات وحالات الدفع ضمن نطاق العمل المحدد."
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
          {getApiErrorMessage(reportData.error, "تعذر تحميل تقرير الاشتراكات. حاول مرة أخرى.")}
        </div>
      )}

      <SubscriptionsReportFilters
        filters={reportData.draftFilters}
        plans={reportData.plans}
        coaches={reportData.coaches}
        validationError={reportData.validationError}
        isFetching={reportData.isFetching}
        onChange={reportData.updateFilter}
        onApply={reportData.applyFilters}
        onReset={reportData.resetFilters}
      />

      <SubscriptionsReportSummary summary={reportData.report.summary} />

      <SubscriptionsReportTable
        rows={reportData.rows}
        summary={reportData.report.summary}
        isLoading={reportData.isFetching}
      />
    </ReportPageShell>
  );
}

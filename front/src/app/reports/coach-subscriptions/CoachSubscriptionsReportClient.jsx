"use client";

import ReportPageShell from "@/components/reports/ReportPageShell";
import SkeletonPage from "@/components/ui/Skeleton";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { printReports } from "../dashboard/reportPrint";
import CoachSubscriptionsReportSummary from "./CoachSubscriptionsReportSummary";
import CoachSubscriptionsReportTable from "./CoachSubscriptionsReportTable";
import { useCoachSubscriptionsReport } from "./useCoachSubscriptionsReport";

function createPrintableReports(report) {
  const metrics = [
    {
      label: "كوتشات الحصص الجماعية",
      value: report.summary.total_group_coaches.toLocaleString("ar"),
    },
    {
      label: "لاعبي الحصص الجماعية",
      value: report.summary.total_group_active_players.toLocaleString("ar"),
    },
    {
      label: "لاعبي الأجهزة العامة",
      value: report.summary.general_equipment_active_players.toLocaleString("ar"),
    },
  ];

  return [
    {
      id: "group-session-coaches",
      title: "كوتشات الحصص الجماعية",
      description: "الأنشطة وعدد اللاعبين ذوي الاشتراكات الفعالة لكل كوتش.",
      metrics,
      columns: [
        { key: "coachName", label: "اسم الكوتش" },
        { key: "activitiesLabel", label: "الأنشطة" },
        { key: "activitiesCount", label: "عدد الأنشطة" },
        { key: "activePlayersCount", label: "اللاعبون الفعالون" },
      ],
      rows: report.groupSessionCoaches,
      emptyMessage: "لا توجد بيانات لكوتشات الحصص الجماعية.",
    },
    {
      id: "general-equipment-subscriptions",
      title: "اشتراكات الأجهزة العامة",
      description: "ملخص اللاعبين الفعالين في التدريب العام.",
      metrics: [
        {
          label: "لاعبي الأجهزة العامة",
          value: report.summary.general_equipment_active_players.toLocaleString("ar"),
        },
      ],
      columns: [
        { key: "title", label: "التصنيف" },
        { key: "activityTypeName", label: "نوع النشاط" },
        { key: "activePlayersCount", label: "اللاعبون الفعالون" },
      ],
      rows: [report.generalEquipment],
      emptyMessage: "لا توجد بيانات لاشتراكات الأجهزة العامة.",
    },
  ];
}

export default function CoachSubscriptionsReportClient() {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const reportData = useCoachSubscriptionsReport();

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
          { type: "stats", count: 3 },
          { type: "custom", className: "h-36 rounded-2xl" },
          { type: "table", rows: 7, columns: 4 },
        ]}
      />
    );
  }

  return (
    <ReportPageShell
      title="تقرير كوتشات الحصص والأجهزة العامة"
      description="عرض الكوتشات والأنشطة واللاعبين ذوي الاشتراكات الفعالة."
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
            "تعذر تحميل تقرير كوتشات الحصص والأجهزة العامة. حاول مرة أخرى.",
          )}
        </div>
      )}

      <CoachSubscriptionsReportSummary
        summary={reportData.report.summary}
        generalEquipment={reportData.report.generalEquipment}
      />
      <CoachSubscriptionsReportTable
        rows={reportData.report.groupSessionCoaches}
        isLoading={reportData.isFetching}
      />
    </ReportPageShell>
  );
}

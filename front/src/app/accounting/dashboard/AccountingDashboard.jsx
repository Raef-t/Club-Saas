"use client";

import { useMemo } from "react";
import SectionCard from "@/components/ui/SectionCard";
import StatsGrid from "@/components/ui/StatsGrid";
import BarChart from "@/components/charts/BarChart";
import LineChart from "@/components/charts/LineChart";
import DashboardMovementItem from "@/components/dashboard/DashboardMovementItem";
import { useGetAccountingDashboardQuery } from "@/lib/api/accountingApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { SalarySummaryPanel, UpcomingPaymentItem } from "./AccountingDashboardWidgets";

/**
 * Composes the live accounting overview from dynamic dashboard API metrics.
 */
export default function AccountingDashboard() {
  const { selectedBranchId } = useManagementBranch();

  const queryParams = useMemo(() => {
    return selectedBranchId && selectedBranchId !== "all"
      ? { branch_id: Number(selectedBranchId) }
      : {};
  }, [selectedBranchId]);

  const { data: dashboardResponse, isLoading } = useGetAccountingDashboardQuery(queryParams);
  const dashboardData = dashboardResponse?.data;

  const overviewStats = dashboardData?.overviewStats || [
    { title: "إيرادات اليوم", value: "0.00 $", change: "+0%", helper: "عن أمس", tone: "yellow" },
    { title: "مصاريف اليوم", value: "0.00 $", change: "+0%", helper: "عن أمس", tone: "green" },
    { title: "صافي الأرباح", value: "0.00 $", change: "مربح", helper: "الفترة الحالية", tone: "purple" },
    { title: "رصيد الصناديق (دولار)", value: "0.00 $", helper: "الرصيد الفعلي", tone: "blue" },
    { title: "رصيد الصناديق (ليرة)", value: "0 ل.س", helper: "الرصيد الفعلي", tone: "blue" },
  ];

  const monthlyProfit = dashboardData?.monthlyProfit || [];
  const comparisonChart = dashboardData?.comparisonChart || {
    labels: ["الأحد", "الاثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"],
    yellow: [0, 0, 0, 0, 0, 0, 0],
    green: [0, 0, 0, 0, 0, 0, 0],
  };

  const upcomingPayments = dashboardData?.upcomingPayments || [];
  const recentTransactions = dashboardData?.recentTransactions || [];
  const salarySummary = dashboardData?.salarySummary || [
    { label: "المدربون", value: "0" },
    { label: "الإجمالي المصروف", value: "0.00 $", tone: "yellow" },
    { label: "الفترة", value: "الحالية" },
  ];

  const periodTitle = dashboardData?.period?.name || "السنة المالية 2026";

  return (
    <div className="space-y-6">
      <StatsGrid items={overviewStats} variant="wide" />

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)_minmax(0,1.15fr)]">
        <SectionCard title="الربح الشهري" className="min-h-[220px] xl:order-2">
          {monthlyProfit.length > 0 ? (
            <BarChart data={monthlyProfit} height={125} />
          ) : (
            <div className="flex h-32 items-center justify-center text-sm text-app-muted">
              لا توجد بيانات أرباح مسجلة
            </div>
          )}
        </SectionCard>

        <SectionCard
          title="الإيرادات مقابل المصاريف"
          action="آخر 7 أيام"
          className="h-[208px] xl:order-1"
        >
          <LineChart data={comparisonChart} />
        </SectionCard>

        <SectionCard
          title="المدفوعات والمستحقات القادمة"
          action="عرض الكل"
          className="h-[206px] xl:order-3"
          contentClassName="space-y-3 px-5 pb-5 overflow-y-auto max-h-[140px]"
        >
          {upcomingPayments.length > 0 ? (
            upcomingPayments.map((item, index) => (
              <UpcomingPaymentItem key={`${item.title}-${index}`} item={item} />
            ))
          ) : (
            <div className="flex h-24 items-center justify-center text-xs text-app-muted">
              لا توجد مستحقات معلقة حالياً
            </div>
          )}
        </SectionCard>
      </section>

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_339px]">
        <SectionCard
          title="آخر المعاملات"
          subtitle="أحدث الحركات المالية المسجلة"
          action="عرض الكل"
          className="min-h-[290px]"
          contentClassName="space-y-4 px-6 pb-5"
        >
          {recentTransactions.length > 0 ? (
            recentTransactions.map((item, index) => (
              <DashboardMovementItem
                key={`${item.title}-${index}`}
                title={item.title}
                description={item.description}
                amount={item.amount}
                meta={item.time}
                direction={item.type}
              />
            ))
          ) : (
            <div className="flex h-36 items-center justify-center text-sm text-app-muted">
              لا توجد قيود أو حركات مالية مسجلة بعد
            </div>
          )}
        </SectionCard>

        <SectionCard title="ملخص رواتب المدربين" subtitle={periodTitle} className="min-h-[282px]">
          <SalarySummaryPanel items={salarySummary} />
        </SectionCard>
      </section>
    </div>
  );
}

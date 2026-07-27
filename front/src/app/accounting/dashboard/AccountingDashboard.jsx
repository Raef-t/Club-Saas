import SectionCard from "@/components/ui/SectionCard";
import StatsGrid from "@/components/ui/StatsGrid";
import BarChart from "@/components/charts/BarChart";
import LineChart from "@/components/charts/LineChart";
import DashboardMovementItem from "@/components/dashboard/DashboardMovementItem";
import {
  comparisonChart,
  monthlyProfit,
  overviewStats,
  recentTransactions,
  salarySummary,
  upcomingPayments,
} from "@/data/mockData";
import { SalarySummaryPanel, UpcomingPaymentItem } from "./AccountingDashboardWidgets";

/**
 * Composes the accounting overview from reusable dashboard widgets.
 */
export default function AccountingDashboard() {
  return (
    <div className="space-y-6">
      <StatsGrid items={overviewStats} variant="wide" />

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)_minmax(0,1.15fr)]">
        <SectionCard title="الربح الشهري" className="h-[206px] xl:order-2">
          <BarChart data={monthlyProfit} />
        </SectionCard>

        <SectionCard
          title="الإيرادات مقابل المصاريف"
          action="آخر 7 أيام"
          className="h-[208px] xl:order-1"
        >
          <LineChart data={comparisonChart} />
        </SectionCard>
        <SectionCard
          title="المدفوعات القادمة"
          action="عرض الكل"
          className="h-[206px] xl:order-3"
          contentClassName="space-y-3 px-5 pb-5"
        >
          {upcomingPayments.map((item, index) => (
            <UpcomingPaymentItem key={`${item.title}-${index}`} item={item} />
          ))}
        </SectionCard>
      </section>

      <section className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_339px]">
        <SectionCard
          title="آخر المعاملات"
          subtitle="أحدث الحركات المالية"
          action="عرض الكل"
          className="min-h-[290px]"
          contentClassName="space-y-4 px-6 pb-5"
        >
          {recentTransactions.map((item, index) => (
            <DashboardMovementItem
              key={`${item.title}-${index}`}
              title={item.title}
              description={item.description}
              amount={item.amount}
              meta={item.time}
              direction={item.type}
            />
          ))}
        </SectionCard>
        <SectionCard title="ملخص رواتب المدربين" subtitle="ديسمبر 2026" className="min-h-[282px]">
          <SalarySummaryPanel items={salarySummary} />
        </SectionCard>
      </section>
    </div>
  );
}

import StatCard from "@/components/ui/StatCard";
import SectionCard from "@/components/ui/SectionCard";
import BarChart from "@/components/charts/BarChart";
import LineChart from "@/components/charts/LineChart";
import { ArrowUpIcon, SealCheckIcon } from "@/components/icons/Icons";
import {
  comparisonChart,
  monthlyProfit,
  overviewStats,
  recentTransactions,
  salarySummary,
  upcomingPayments,
} from "@/data/mockData";

function PaymentItem({ item }) {
  return (
    <div className="flex h-12 items-center justify-between rounded-lg bg-app-card-soft px-3">
      <div className="text-start text-sm font-medium text-app-yellow">
        {item.amount}
      </div>
      <div className="text-end">
        <h4 className="text-sm font-medium text-app-text">{item.title}</h4>
        <p className="text-xs text-app-muted">{item.date}</p>
      </div>
    </div>
  );
}

function TransactionItem({ item }) {
  const isIn = item.type === "in";
  return (
    <div className="flex h-12 items-center gap-3 rounded-lg bg-app-card-soft px-3">
      <div
        className={`grid size-9 place-items-center rounded-md ${isIn ? "bg-[rgba(21,207,133,0.20)] text-app-green" : "bg-[rgba(207,21,21,0.20)] text-app-red"}`}
      >
        <ArrowUpIcon
          className={`size-5 ${isIn ? "rotate-[225deg]" : "rotate-45"}`}
        />
      </div>
      <div className="min-w-0 flex-1 text-end">
        <h4 className="truncate text-sm font-medium text-app-text">
          {item.title}
        </h4>
        <p className="truncate text-xs text-app-muted">{item.description}</p>
      </div>
      <div className="text-center text-xs text-app-muted">
        <strong
          className={`block text-sm font-medium ${isIn ? "text-app-green" : "text-app-red"}`}
        >
          {item.amount}
        </strong>
        {item.time}
      </div>
    </div>
  );
}

export default function AccountingDashboardPage() {
  return (
    <div className="space-y-6">
      <section className="grid grid-auto-fit gap-5">
        {overviewStats.map((stat) => (
          <StatCard key={stat.title} {...stat} />
        ))}
      </section>

      <section className="grid gap-5 xl:grid-cols-[343px_295px_339px]">
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
            <PaymentItem key={index} item={item} />
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
            <TransactionItem key={index} item={item} />
          ))}
        </SectionCard>
        <SectionCard
          title="ملخص رواتب المدربين"
          subtitle="ديسمبر 2026"
          className="min-h-[282px]"
        >
          <div className="flex justify-center gap-5 px-5 pt-8" dir="ltr">
            {salarySummary.map((item) => (
              <div
                key={item.label}
                className="grid h-24 w-24 place-items-center rounded-lg bg-app-card-soft text-center"
              >
                <span className="text-sm text-[#b3b1b1]">{item.label}</span>
                <strong
                  className={`text-xl font-medium ${item.tone === "yellow" ? "text-app-yellow" : "text-white"}`}
                >
                  {item.value}
                </strong>
              </div>
            ))}
          </div>
          <div className="mt-10 flex items-center justify-center gap-4 text-xs text-app-muted">
            <SealCheckIcon className="size-6 text-app-green" />
            تم احتساب الرواتب تلقائياً وفق نظام النسب
          </div>
        </SectionCard>
      </section>
    </div>
  );
}

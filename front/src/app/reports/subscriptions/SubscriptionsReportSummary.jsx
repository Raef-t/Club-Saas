import SectionCard from "@/components/ui/SectionCard";
import StatsGrid from "@/components/ui/StatsGrid";
import { formatMoney } from "@/lib/utils";

function SummaryGroup({ title, items }) {
  return (
    <SectionCard title={title} contentClassName="border-t border-app-line p-4">
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {items.map((item) => (
          <div
            key={item.label}
            className="rounded-xl border border-app-line bg-app-card-soft px-4 py-3 text-right"
          >
            <span className="text-xs text-app-muted-light">{item.label}</span>
            <strong
              className={`mt-2 block text-xl font-medium ${item.className || "text-app-text"}`}
            >
              {item.value.toLocaleString("ar")}
            </strong>
          </div>
        ))}
      </div>
    </SectionCard>
  );
}

export default function SubscriptionsReportSummary({ summary }) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;
  const financialStats = [
    {
      title: "إجمالي الاشتراكات",
      value: summary.total_subscriptions.toLocaleString("ar"),
      helper: "ضمن نتائج التقرير الحالية",
      tone: "yellow",
      iconKey: "subscriptions",
    },
    {
      title: "إجمالي الإيراد",
      value: formatMoney(summary.total_revenue, currency),
      helper: "القيمة الكلية للاشتراكات",
      tone: "blue",
      iconKey: "subscriptions",
    },
    {
      title: "المبلغ المدفوع",
      value: formatMoney(summary.total_paid, currency),
      helper: "إجمالي المبالغ المحصلة",
      tone: "green",
      iconKey: "subscriptions",
    },
    {
      title: "المبلغ المتبقي",
      value: formatMoney(summary.total_remaining, currency),
      helper: "إجمالي الذمم المتبقية",
      tone: "orange",
      iconKey: "subscriptions",
    },
  ];

  return (
    <div className="space-y-4">
      <StatsGrid items={financialStats} />
      <div className="grid gap-4 xl:grid-cols-2">
        <SummaryGroup
          title="توزيع حالات الاشتراك"
          items={[
            { label: "فعال", value: summary.active_count, className: "text-app-green" },
            { label: "منتهي", value: summary.finished_count, className: "text-app-red" },
            { label: "مجمّد", value: summary.frozen_count, className: "text-[#d99300]" },
            { label: "ملغى", value: summary.terminated_count, className: "text-app-muted-light" },
          ]}
        />
        <SummaryGroup
          title="توزيع حالات الدفع"
          items={[
            {
              label: "مدفوع بالكامل",
              value: summary.fully_paid_count,
              className: "text-app-green",
            },
            {
              label: "مدفوع جزئياً",
              value: summary.partially_paid_count,
              className: "text-[#d99300]",
            },
            { label: "غير مدفوع", value: summary.unpaid_count, className: "text-app-red" },
          ]}
        />
      </div>
    </div>
  );
}

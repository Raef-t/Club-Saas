import StatsGrid from "@/components/ui/StatsGrid";
import SectionCard from "@/components/ui/SectionCard";
import { formatMoney } from "@/lib/utils";

export default function FrozenTerminatedReportSummary({ summary }) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;

  const kpiItems = [
    {
      title: "إجمالي السجلات",
      value: summary.total_records.toLocaleString("ar"),
      helper: "مجمدة وملغاة",
      tone: "yellow",
      iconKey: "subscriptions",
    },
    {
      title: "الاشتراكات المجمدة",
      value: summary.total_frozen.toLocaleString("ar"),
      helper: "إيقاف مؤقت للاشتراك",
      tone: "blue",
      iconKey: "lockers",
    },
    {
      title: "الاشتراكات الملغاة",
      value: summary.total_terminated.toLocaleString("ar"),
      helper: "تم إنهاؤها أو إلغاؤها",
      tone: "red",
      iconKey: "attendance",
    },
  ];

  return (
    <div className="space-y-4">
      <StatsGrid items={kpiItems} />

      <div className="grid gap-4 md:grid-cols-2">
        <SectionCard
          title="إجمالي الإيرادات المجمدة (Frozen)"
          subtitle="قيمة الاشتراكات المعلقة مؤقتاً في النظام"
          contentClassName="p-5 border-t border-app-line"
        >
          <div className="flex items-baseline justify-between gap-2">
            <span className="text-sm text-app-muted-light">الإيراد المجمد:</span>
            <span className="text-2xl font-bold tracking-tight text-cyan-400">
              {formatMoney(summary.total_frozen_revenue, currency)}
            </span>
          </div>
          <div className="mt-3 text-xs text-app-muted">
            مرتبطة بـ {summary.total_frozen.toLocaleString("ar")} اشتراك مجمّد حالياً بانتظار استئناف النشاط.
          </div>
        </SectionCard>

        <SectionCard
          title="إجمالي الإيرادات المفقودة بالإلغاء (Terminated)"
          subtitle="الخسارة المالية الناتجة عن إلغاء أو إنهاء الاشتراكات"
          contentClassName="p-5 border-t border-app-line"
        >
          <div className="flex items-baseline justify-between gap-2">
            <span className="text-sm text-app-muted-light">الإيراد المفقود:</span>
            <span className="text-2xl font-bold tracking-tight text-app-red">
              {formatMoney(summary.total_lost_terminated_revenue, currency)}
            </span>
          </div>
          <div className="mt-3 text-xs text-app-muted">
            ناتجة عن {summary.total_terminated.toLocaleString("ar")} اشتراك ملغى في النطاق المحدد.
          </div>
        </SectionCard>
      </div>
    </div>
  );
}

import StatsGrid from "@/components/ui/StatsGrid";
import SectionCard from "@/components/ui/SectionCard";
import { formatMoney } from "@/lib/utils";

export default function RenewalStatusReportSummary({ summary }) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;

  const kpiItems = [
    {
      title: "إجمالي السجلات",
      value: summary.total_records.toLocaleString("ar"),
      helper: "إجمالي الاشتراكات المطابقة",
      tone: "yellow",
      iconKey: "subscriptions",
    },
    {
      title: "منتهي ولم يجدد",
      value: summary.total_expired_non_renewed.toLocaleString("ar"),
      helper: "اشتراكات بحاجة لمتابعة",
      tone: "red",
      iconKey: "attendance",
    },
    {
      title: "تم التجديد",
      value: summary.total_renewed.toLocaleString("ar"),
      helper: "اشتراكات تم تمديدها بنجاح",
      tone: "green",
      iconKey: "members",
    },
    {
      title: "نسبة التجديد",
      value: `${summary.renewal_rate_percentage.toLocaleString("ar", {
        maximumFractionDigits: 2,
      })}%`,
      helper: "معدل التجديد الإجمالي",
      tone: "blue",
      iconKey: "activities",
    },
  ];

  return (
    <div className="space-y-4">
      <StatsGrid items={kpiItems} />

      <div className="grid gap-4 md:grid-cols-2">
        <SectionCard
          title="الإيرادات المفقودة المحتملة"
          subtitle="قيمة الاشتراكات المنتهية التي لم تجدد بعد"
          contentClassName="p-5 border-t border-app-line"
        >
          <div className="flex items-baseline justify-between gap-2">
            <span className="text-sm text-app-muted-light">إجمالي الإيراد غير المحقق:</span>
            <span className="text-2xl font-bold tracking-tight text-app-red">
              {formatMoney(summary.total_lost_potential_revenue, currency)}
            </span>
          </div>
          <div className="mt-3 text-xs text-app-muted">
            يمثل المبلغ التقديري للفرص الضائعة من {summary.total_expired_non_renewed.toLocaleString("ar")} اشتراك غير مجدد.
          </div>
        </SectionCard>

        <SectionCard
          title="إيرادات التجديد المحققة"
          subtitle="القيمة المالية التي تم تحصيلها عبر التجديدات الجديدة"
          contentClassName="p-5 border-t border-app-line"
        >
          <div className="flex items-baseline justify-between gap-2">
            <span className="text-sm text-app-muted-light">إجمالي مبالغ التجديد:</span>
            <span className="text-2xl font-bold tracking-tight text-app-green">
              {formatMoney(summary.total_renewed_revenue, currency)}
            </span>
          </div>
          <div className="mt-3 text-xs text-app-muted">
            تم تحقيقها من تجديد {summary.total_renewed.toLocaleString("ar")} لاعب بنجاح.
          </div>
        </SectionCard>
      </div>
    </div>
  );
}

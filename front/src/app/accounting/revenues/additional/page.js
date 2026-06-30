import StatsGrid from "@/components/ui/StatsGrid";
import Tabs from "@/components/ui/Tabs";
import DataTable from "@/components/ui/DataTable";
import { additionalRevenueColumns, additionalRevenues, revenueStats } from "@/data/mockData";

const tabs = [
  { title: "إيرادات الاشتراكات", href: "/accounting/revenues" },
  { title: "إيرادات إضافية", href: "/accounting/revenues/additional" },
  { title: "العروض الترويجية", href: "/accounting/revenues/offers" },
];

export default function AdditionalRevenuesPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={revenueStats} />
      <Tabs items={tabs} activeHref="/accounting/revenues/additional" />
      <DataTable
        title="الإيرادات الإضافية"
        subtitle="جميع الإيرادات المرتبطة بالخدمات الخارجية"
        columns={additionalRevenueColumns}
        rows={additionalRevenues}
        addLabel="إضافة إيراد"
      />
    </div>
  );
}

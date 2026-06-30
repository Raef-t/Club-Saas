import StatsGrid from "@/components/ui/StatsGrid";
import Tabs from "@/components/ui/Tabs";
import DataTable from "@/components/ui/DataTable";
import { revenueColumns, revenues, revenueStats } from "@/data/mockData";

const tabs = [
  { title: "إيرادات الاشتراكات", href: "/accounting/revenues" },
  { title: "إيرادات إضافية", href: "/accounting/revenues/additional" },
  { title: "العروض الترويجية", href: "/accounting/revenues/offers" },
];

export default function RevenuesPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={revenueStats} />
      <Tabs items={tabs} activeHref="/accounting/revenues" />
      <DataTable
        title="اشتراكات الأعضاء"
        subtitle="جميع الاشتراكات والمدفوعات المرتبطة بالعضويات"
        columns={revenueColumns}
        rows={revenues}
        addLabel="إضافة إيراد"
      />
    </div>
  );
}

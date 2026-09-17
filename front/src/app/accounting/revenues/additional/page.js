import StatsGrid from "@/components/ui/StatsGrid";
import Tabs from "@/components/ui/Tabs";
import DataTable from "@/components/ui/DataTable";
import { additionalRevenueColumns, additionalRevenues, revenueStats } from "@/data/mockData";
import { verifyPageAccess } from "@/lib/server/auth";

const tabs = [
  { title: "إيرادات الاشتراكات", href: "/accounting/revenues" },
  { title: "إيرادات إضافية", href: "/accounting/revenues/additional" },
];

export default async function AdditionalRevenuesPage() {
  await verifyPageAccess("/accounting/revenues/additional");

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

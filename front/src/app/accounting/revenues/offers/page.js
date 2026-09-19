import StatsGrid from "@/components/ui/StatsGrid";
import Tabs from "@/components/ui/Tabs";
import EmptyState from "@/components/ui/EmptyState";
import { revenueStats } from "@/data/mockData";
import { verifyPageAccess } from "@/lib/server/auth";

const tabs = [
  { title: "إيرادات الاشتراكات", href: "/accounting/revenues" },
  { title: "إيرادات إضافية", href: "/accounting/revenues/additional" },
  { title: "العروض الترويجية", href: "/accounting/revenues/offers" },
];

export default async function OffersPage() {
  await verifyPageAccess("/accounting/revenues/offers");

  return (
    <div className="space-y-6">
      <StatsGrid items={revenueStats} />
      <Tabs items={tabs} activeHref="/accounting/revenues/offers" />
      <EmptyState
        title="لا توجد عروض ترويجية حالياً"
        description="يمكنك لاحقاً ربط هذه الصفحة بواجهة إضافة العروض أو API خاص بالعروض الموسمية."
      />
    </div>
  );
}

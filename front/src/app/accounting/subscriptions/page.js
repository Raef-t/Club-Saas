import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { subscriptionColumns, subscriptions, subscriptionStats } from "@/data/mockData";

export default function SubscriptionsPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={subscriptionStats} />
      <DataTable
        title="مدفوعات الأعضاء"
        subtitle="كل دفعات الأعضاء الحالية والقديمة والمتأخرة"
        columns={subscriptionColumns}
        rows={subscriptions}
        addLabel="تسجيل دفعة"
      />
    </div>
  );
}

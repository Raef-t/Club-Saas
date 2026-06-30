import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { expenseColumns, expenses, expenseStats } from "@/data/mockData";

export default function ExpensesPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={expenseStats} />
      <DataTable
        title="اشتراكات الأعضاء"
        subtitle="جميع المصاريف الثابتة والمتغيرة للنادي"
        columns={expenseColumns}
        rows={expenses}
        addLabel="مصروف جديد"
      />
    </div>
  );
}

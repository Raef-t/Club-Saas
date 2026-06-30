import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { cashboxColumns, cashboxRows, cashboxStats } from "@/data/mockData";

export default function CashboxPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={cashboxStats} />
      <DataTable
        title="حركة الصندوق اليومي"
        subtitle="جميع القيود المسجلة اليوم أو حسب تاريخ محدد"
        columns={cashboxColumns}
        rows={cashboxRows}
        addLabel="قيد جديد"
      />
    </div>
  );
}

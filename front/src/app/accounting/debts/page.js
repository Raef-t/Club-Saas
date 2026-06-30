import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { debtColumns, debts, debtStats } from "@/data/mockData";

export default function DebtsPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={debtStats} />
      <DataTable
        title="تقرير أعمار الديون"
        subtitle="تصنيف الذمم حسب فترة الاستحقاق"
        columns={debtColumns}
        rows={debts}
        addLabel="إضافة ذمة"
      />
    </div>
  );
}

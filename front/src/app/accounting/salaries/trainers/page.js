import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { trainersSalaries, trainersSalaryColumns, trainersSalaryStats } from "@/data/mockData";

export default function TrainersSalaryPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={trainersSalaryStats} />
      <DataTable
        title="كشف رواتب ديسمبر 2026"
        subtitle="جدول رواتب المدربين حسب الراتب أو نظام النسب"
        columns={trainersSalaryColumns}
        rows={trainersSalaries}
        addLabel="إنشاء كشف"
      />
    </div>
  );
}

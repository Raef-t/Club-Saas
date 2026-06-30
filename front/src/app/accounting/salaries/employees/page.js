import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { employeesSalaries, employeesSalaryColumns, employeesSalaryStats } from "@/data/mockData";

export default function EmployeesSalaryPage() {
  return (
    <div className="space-y-6">
      <StatsGrid items={employeesSalaryStats} />
      <DataTable
        title="كشف رواتب الموظفين"
        subtitle="ديسمبر 2025 - متكامل مع نظام الحضور"
        columns={employeesSalaryColumns}
        rows={employeesSalaries}
        addLabel="إنشاء كشف"
      />
    </div>
  );
}

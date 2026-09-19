import StatsGrid from "@/components/ui/StatsGrid";
import DataTable from "@/components/ui/DataTable";
import { employeesSalaries, employeesSalaryColumns, employeesSalaryStats } from "@/data/mockData";
import { verifyPageAccess } from "@/lib/server/auth";

export default async function EmployeesSalaryPage() {
  await verifyPageAccess("/accounting/salaries/employees");

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

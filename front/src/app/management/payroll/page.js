import PayrollClient from "./PayrollClient";
import { createPayrollAction } from "./payrollUtils";
import { verifyPageAccess } from "@/lib/server/auth";

export const metadata = {
  title: "الرواتب | TechnoGYM",
  description: "توليد ومراجعة وتثبيت رواتب موظفي النادي.",
};

export default async function PayrollPage({ searchParams }) {
  await verifyPageAccess("/management/payroll");
  const query = await searchParams;
  return <PayrollClient initialAction={createPayrollAction(query)} />;
}

import PayrollClient from "./PayrollClient";
import { createPayrollAction } from "./payrollUtils";

export const metadata = {
  title: "الرواتب | TechnoGYM",
  description: "توليد ومراجعة وتثبيت رواتب موظفي النادي.",
};

export default async function PayrollPage({ searchParams }) {
  const query = await searchParams;
  return <PayrollClient initialAction={createPayrollAction(query)} />;
}

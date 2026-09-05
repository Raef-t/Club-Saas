import FinancialReportsClient from "./FinancialReportsClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "التقارير والقوائم المالية الختامية | نظام المحاسبة",
};

export default async function FinancialReportsPage() {
  const { token } = await verifyPageAccess("/accounting/reports");
  let initialPeriods = [];

  try {
    const periodsRes = await requestBackend("accounting/periods", { token });
    initialPeriods = periodsRes?.data || [];
  } catch {
    initialPeriods = [];
  }

  return <FinancialReportsClient initialPeriods={initialPeriods} />;
}

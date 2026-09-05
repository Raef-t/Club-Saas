import SalariesClient from "./SalariesClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "رواتب الكوادر والموظفين | نظام المحاسبة",
};

export default async function SalariesPage() {
  const { token } = await verifyPageAccess("/accounting/salaries");
  let initialPayments = [];
  let initialStaff = [];
  let initialSafes = [];
  let initialPeriods = [];

  try {
    const [paymentsRes, staffRes, safesRes, periodsRes] = await Promise.all([
      requestBackend("accounting/salary-payments", { token }),
      requestBackend("staff", { token, params: { per_page: "all" } }),
      requestBackend("accounting/safes", { token }),
      requestBackend("accounting/periods", { token }),
    ]);
    initialPayments = paymentsRes?.data || [];
    initialStaff = staffRes?.data || [];
    initialSafes = safesRes?.data || [];
    initialPeriods = periodsRes?.data || [];
  } catch {
    initialPayments = [];
    initialStaff = [];
    initialSafes = [];
    initialPeriods = [];
  }

  return (
    <SalariesClient
      initialPayments={initialPayments}
      initialStaff={initialStaff}
      initialSafes={initialSafes}
      initialPeriods={initialPeriods}
    />
  );
}

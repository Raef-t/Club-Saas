import SalariesClient from "./SalariesClient";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "رواتب الكوادر والموظفين | نظام المحاسبة",
};

export default async function SalariesPage() {
  const { token } = await verifySession();
  let initialPayments = [];
  let initialStaff = [];
  let initialSafes = [];
  let initialPeriods = [];

  try {
    const [paymentsRes, staffRes, safesRes, periodsRes] = await Promise.all([
      requestBackend("accounting/salary-payments", { token }),
      requestBackend("staff", { token }),
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

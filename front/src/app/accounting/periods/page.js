import PeriodsClient from "./PeriodsClient";
import { verifyPageAccess } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";

export const metadata = {
  title: "الفترات المالية والإقفال المحاسبي | نظام المحاسبة",
};

export default async function PeriodsPage() {
  const { token } = await verifyPageAccess("/accounting/periods");
  let initialPeriods = [];

  try {
    const res = await requestBackend("accounting/periods", { token });
    initialPeriods = res?.data || [];
  } catch {
    initialPeriods = [];
  }

  return <PeriodsClient initialPeriods={initialPeriods} />;
}

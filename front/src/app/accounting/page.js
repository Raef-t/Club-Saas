import AccountingDashboard from "./dashboard/AccountingDashboard";
import { verifyPageAccess } from "@/lib/server/auth";

/**
 * Renders the accounting dashboard route.
 */
export default async function AccountingDashboardPage() {
  await verifyPageAccess("/accounting");

  return <AccountingDashboard />;
}

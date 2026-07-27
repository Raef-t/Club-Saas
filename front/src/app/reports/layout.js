import { cookies } from "next/headers";
import AppShell from "@/components/layout/AppShell";
import ReportsSidebar from "@/components/layout/ReportsSidebar";
import { ManagementBranchProvider } from "@/lib/ManagementBranchContext";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { normalizeSelectedBranchId, REPORTS_BRANCH_COOKIE } from "@/lib/managementBranchUtils";

/**
 * Loads the branches required by the reports filter.
 */
async function loadReportBranches(token) {
  try {
    return await requestBackend("branches", { token });
  } catch {
    return [];
  }
}

/**
 * Provides the verified user and report-specific branch selection.
 */
export default async function ReportsLayout({ children }) {
  const session = await verifySession();
  const [branches, cookieStore] = await Promise.all([loadReportBranches(session.token), cookies()]);
  const initialSelectedBranchId = normalizeSelectedBranchId(
    cookieStore.get(REPORTS_BRANCH_COOKIE)?.value,
    branches,
  );

  return (
    <ManagementBranchProvider
      initialBranches={branches}
      initialSelectedBranchId={initialSelectedBranchId}
      cookieName={REPORTS_BRANCH_COOKIE}
      cookiePath="/reports"
    >
      <AppShell sidebar={<ReportsSidebar />} currentUser={session.user}>
        {children}
      </AppShell>
    </ManagementBranchProvider>
  );
}

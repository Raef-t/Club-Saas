import { cookies } from "next/headers";
import AppShell from "@/components/layout/AppShell";
import ReportsSidebar from "@/components/layout/ReportsSidebar";
import { ManagementBranchProvider } from "@/lib/ManagementBranchContext";
import { verifySession } from "@/lib/server/auth";
import { loadAvailableBranches } from "@/lib/server/backend";
import { canAccessAllBranches } from "@/lib/permissions";
import { normalizeSelectedBranchId, REPORTS_BRANCH_COOKIE } from "@/lib/managementBranchUtils";

/**
 * Provides the verified user and report-specific branch selection.
 */
export default async function ReportsLayout({ children }) {
  const session = await verifySession();
  const isAllBranchesAllowed = canAccessAllBranches(session.user);
  const userBranchId = session.user?.branch_id;

  const [branches, cookieStore] = await Promise.all([
    loadAvailableBranches(session.token, userBranchId),
    cookies(),
  ]);

  let effectiveBranches = [];
  if (isAllBranchesAllowed) {
    effectiveBranches = branches;
  } else if (userBranchId) {
    const userBranch = branches.find((b) => String(b.id) === String(userBranchId));
    effectiveBranches = [
      userBranch || branches[0] || { id: Number(userBranchId) || userBranchId, name: "تكنو جيم بنات" },
    ];
  }

  const rawBranchCookie = cookieStore.get(REPORTS_BRANCH_COOKIE)?.value;
  const candidateBranchId = isAllBranchesAllowed
    ? rawBranchCookie || (userBranchId ? String(userBranchId) : undefined)
    : userBranchId
      ? String(userBranchId)
      : undefined;

  const initialSelectedBranchId = normalizeSelectedBranchId(
    candidateBranchId,
    effectiveBranches,
    { fallbackToFirst: !isAllBranchesAllowed },
  );

  return (
    <ManagementBranchProvider
      initialBranches={effectiveBranches}
      initialSelectedBranchId={initialSelectedBranchId}
      canSelectAllBranches={isAllBranchesAllowed}
      cookieName={REPORTS_BRANCH_COOKIE}
      cookiePath="/reports"
    >
      <AppShell sidebar={<ReportsSidebar />} currentUser={session.user}>
        {children}
      </AppShell>
    </ManagementBranchProvider>
  );
}

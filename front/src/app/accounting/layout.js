import { cookies } from "next/headers";
import AppShell from "@/components/layout/AppShell";
import AccountingSidebar from "@/components/layout/AccountingSidebar";
import { ManagementBranchProvider } from "@/lib/ManagementBranchContext";
import { verifySession } from "@/lib/server/auth";
import { loadAvailableBranches } from "@/lib/server/backend";
import { canAccessAllBranches } from "@/lib/permissions";
import { MANAGEMENT_BRANCH_COOKIE, normalizeSelectedBranchId } from "@/lib/managementBranchUtils";

export default async function AccountingLayout({ children }) {
  const session = await verifySession();
  const isAllBranchesAllowed = canAccessAllBranches(session.user);
  const userBranchId = session.user?.branch_id;

  const [branches, cookieStore] = await Promise.all([
    loadAvailableBranches(session?.token, userBranchId),
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

  const rawBranchCookie = cookieStore.get(MANAGEMENT_BRANCH_COOKIE)?.value;
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
    >
      <AppShell sidebar={<AccountingSidebar />} currentUser={session.user}>
        {children}
      </AppShell>
    </ManagementBranchProvider>
  );
}

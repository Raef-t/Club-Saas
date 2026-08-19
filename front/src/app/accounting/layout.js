import { cookies } from "next/headers";
import AppShell from "@/components/layout/AppShell";
import AccountingSidebar from "@/components/layout/AccountingSidebar";
import { ManagementBranchProvider } from "@/lib/ManagementBranchContext";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { MANAGEMENT_BRANCH_COOKIE, normalizeSelectedBranchId } from "@/lib/managementBranchUtils";

/**
 * Loads the branches required by the persistent branch selector.
 */
async function loadAccountingBranches(token) {
  try {
    return await requestBackend("branches", { token });
  } catch {
    return [];
  }
}

export default async function AccountingLayout({ children }) {
  const session = await verifySession();
  const [branches, cookieStore] = await Promise.all([
    loadAccountingBranches(session?.token),
    cookies(),
  ]);
  const initialSelectedBranchId = normalizeSelectedBranchId(
    cookieStore.get(MANAGEMENT_BRANCH_COOKIE)?.value,
    branches,
  );

  return (
    <ManagementBranchProvider
      initialBranches={branches}
      initialSelectedBranchId={initialSelectedBranchId}
    >
      <AppShell sidebar={<AccountingSidebar />} currentUser={session.user}>
        {children}
      </AppShell>
    </ManagementBranchProvider>
  );
}

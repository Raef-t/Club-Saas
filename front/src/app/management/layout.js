import { cookies } from "next/headers";
import AppShell from "@/components/layout/AppShell";
import ManagementSidebar from "@/components/layout/ManagementSidebar";
import { ManagementBranchProvider } from "@/lib/ManagementBranchContext";
import { verifySession } from "@/lib/server/auth";
import { requestBackend } from "@/lib/server/backend";
import { MANAGEMENT_BRANCH_COOKIE, normalizeSelectedBranchId } from "@/lib/managementBranchUtils";

/**
 * Loads the branches required by the persistent management selector.
 */
async function loadManagementBranches(token) {
  try {
    return await requestBackend("branches", { token });
  } catch {
    return [];
  }
}

/**
 * Provides the verified user and global branch state to management pages.
 */
export default async function ManagementLayout({ children }) {
  const session = await verifySession();
  const [branches, cookieStore] = await Promise.all([
    loadManagementBranches(session.token),
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
      <AppShell sidebar={<ManagementSidebar />} currentUser={session.user}>
        {children}
      </AppShell>
    </ManagementBranchProvider>
  );
}

import AppShell from "@/components/layout/AppShell";
import AccountingSidebar from "@/components/layout/AccountingSidebar";
import { verifySession } from "@/lib/server/auth";

export default async function AccountingLayout({ children }) {
  const session = await verifySession();

  return (
    <AppShell sidebar={<AccountingSidebar />} currentUser={session.user}>
      {children}
    </AppShell>
  );
}

import AppShell from "@/components/layout/AppShell";
import AccountingSidebar from "@/components/layout/AccountingSidebar";

export default function AccountingLayout({ children }) {
  return <AppShell sidebar={<AccountingSidebar />}>{children}</AppShell>;
}

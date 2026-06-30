import AppShell from "@/components/layout/AppShell";
import ManagementSidebar from "@/components/layout/ManagementSidebar";

export default function ManagementLayout({ children }) {
  return <AppShell sidebar={<ManagementSidebar />}>{children}</AppShell>;
}

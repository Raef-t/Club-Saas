import AppShell from "@/components/layout/AppShell";
import ReportsSidebar from "@/components/layout/ReportsSidebar";

export default function ReportsLayout({ children }) {
  return <AppShell sidebar={<ReportsSidebar />}>{children}</AppShell>;
}

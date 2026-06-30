import IconRail from "@/components/layout/IconRail";
import Navbar from "@/components/layout/Navbar";

export default function AppShell({ children, sidebar }) {
  return (
    <div className="dashboard-bg min-h-screen p-4 text-app-text md:p-6">
      <div className="app-layout-grid mx-auto grid max-w-[1440px] gap-3">
        <IconRail />
        {sidebar}
        <main className="min-w-0 flex flex-col min-h-[calc(100vh-3rem)]">
          <Navbar />
          <div className="mt-7 flex-1">{children}</div>
          
          <footer className="mt-10 py-4 text-center text-xs text-app-muted-light" dir="ltr">
            &copy; {new Date().getFullYear()} <span className="text-app-orange font-medium">ISS Group</span>. All Rights Reserved.
          </footer>
        </main>
      </div>
    </div>
  );
}

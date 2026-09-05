"use client";

import { useState, useEffect } from "react";
import { usePathname } from "next/navigation";
import React from "react";
import IconRail from "@/components/layout/IconRail";
import Navbar from "@/components/layout/Navbar";
import { XIcon } from "@/components/icons/Icons";
import { PermissionProvider } from "@/lib/PermissionContext";
import RouteAccessBoundary from "@/components/auth/RouteAccessBoundary";

export default function AppShell({ children, sidebar, currentUser }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const pathname = usePathname();

  // Close sidebar on page change
  useEffect(() => {
    setSidebarOpen(false);
  }, [pathname]);

  // Clone sidebar element to inject custom mobile styles when in mobile view
  const mobileSidebar = sidebar
    ? React.cloneElement(sidebar, {
        className:
          "w-full min-h-full overflow-visible px-3 py-6 bg-transparent border-0 shadow-none sticky-none lg:block",
      })
    : null;

  return (
    <PermissionProvider user={currentUser}>
      <div className="dashboard-bg min-h-screen p-3 text-app-text sm:p-4 md:p-6">
        {/* Mobile Sidebar Off-canvas Drawer */}
        {sidebarOpen && (
          <div className="fixed inset-0 z-50 flex justify-start lg:hidden" dir="rtl">
            {/* Backdrop Overlay */}
            <div
              className="fixed inset-0 bg-[var(--app-overlay)] backdrop-blur-sm animate-fade-in"
              onClick={() => setSidebarOpen(false)}
            />

            {/* Drawer Container */}
            <div className="relative flex h-full w-[320px] max-w-[85vw] overflow-hidden border-e border-app-line bg-app-panel shadow-2xl animate-slide-in-rtl">
              {/* Inner contents */}
              <div className="flex h-full w-full overflow-hidden">
                {/* Right Side: Mini Icon rail for system switching & Close Button */}
                <div className="flex w-[68px] shrink-0 flex-col items-center gap-2 border-e border-app-line bg-app-panel-soft/50 py-4">
                  {/* Close Button */}
                  <button
                    onClick={() => setSidebarOpen(false)}
                    className="grid size-9 shrink-0 place-items-center rounded-xl bg-app-card-soft text-app-muted hover:text-app-text hover:bg-app-line/30 border border-app-line transition shadow-sm"
                    title="إغلاق القائمة"
                    aria-label="إغلاق القائمة الجانبية"
                  >
                    <XIcon className="size-5" />
                  </button>

                  <IconRail
                    className="flex flex-col items-center justify-between flex-1 w-full min-h-0"
                    isMobile
                  />
                </div>

                {/* Left Side: System specific Sidebar links */}
                <div className="sidebar-scrollbar min-w-0 flex-1 overflow-y-auto overflow-x-hidden">
                  {mobileSidebar}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Main Layout Grid */}
        <div className="app-layout-grid mx-auto grid max-w-[1440px] gap-3">
          <IconRail />
          {sidebar}
          <main className="flex min-h-[calc(100vh-3rem)] min-w-0 flex-col overflow-hidden lg:overflow-visible">
            <Navbar onMenuClick={() => setSidebarOpen(true)} initialUser={currentUser} />
            <div className="mt-5 min-w-0 flex-1 sm:mt-7">
              <RouteAccessBoundary>{children}</RouteAccessBoundary>
            </div>

            <footer className="mt-10 py-4 text-center text-xs text-app-muted-light" dir="ltr">
              &copy; {new Date().getFullYear()}{" "}
              <span className="text-app-orange font-medium">ISS Group</span>. All Rights Reserved.
            </footer>
          </main>
        </div>
      </div>
    </PermissionProvider>
  );
}

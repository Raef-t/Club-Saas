"use client";

import { useState, useEffect } from "react";
import { usePathname } from "next/navigation";
import React from "react";
import IconRail from "@/components/layout/IconRail";
import Navbar from "@/components/layout/Navbar";
import { XIcon } from "@/components/icons/Icons";

export default function AppShell({ children, sidebar }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const pathname = usePathname();

  // Close sidebar on page change
  useEffect(() => {
    setSidebarOpen(false);
  }, [pathname]);

  // Clone sidebar element to inject custom mobile styles when in mobile view
  const mobileSidebar = sidebar
    ? React.cloneElement(sidebar, {
        className: "w-full h-full overflow-y-auto px-4 py-6 bg-transparent border-0 shadow-none sticky-none lg:block",
      })
    : null;

  return (
    <div className="dashboard-bg min-h-screen p-3 text-app-text sm:p-4 md:p-6">
      {/* Mobile Sidebar Off-canvas Drawer */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-50 flex justify-start lg:hidden" dir="rtl">
          {/* Backdrop Overlay */}
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-sm animate-fade-in"
            onClick={() => setSidebarOpen(false)}
          />

          {/* Drawer Container */}
          <div className="relative flex h-full w-[320px] max-w-[85vw] bg-app-panel border-l border-app-line shadow-2xl animate-slide-in-rtl">
            {/* Close Button */}
            <button
              onClick={() => setSidebarOpen(false)}
              className="absolute top-4 left-4 z-50 grid size-8 place-items-center rounded-lg bg-app-card-soft text-app-muted hover:text-app-text border border-app-line transition"
              title="إغلاق القائمة"
            >
              <XIcon className="size-5" />
            </button>

            {/* Inner contents */}
            <div className="flex h-full w-full">
              {/* Right Side: Mini Icon rail for system switching */}
              <div className="w-[68px] border-l border-app-line flex flex-col items-center py-6 bg-app-panel-soft/50">
                <IconRail className="flex flex-col items-center justify-between h-full w-full" isMobile />
              </div>

              {/* Left Side: System specific Sidebar links */}
              <div className="flex-1 min-w-0 overflow-y-auto">
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
          <Navbar onMenuClick={() => setSidebarOpen(true)} />
          <div className="mt-5 min-w-0 flex-1 sm:mt-7">{children}</div>

          <footer className="mt-10 py-4 text-center text-xs text-app-muted-light" dir="ltr">
            &copy; {new Date().getFullYear()} <span className="text-app-orange font-medium">ISS Group</span>. All Rights Reserved.
          </footer>
        </main>
      </div>
    </div>
  );
}

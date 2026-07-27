"use client";

import { useState, useEffect } from "react";
import { usePathname } from "next/navigation";
import { pageMeta } from "@/data/mockData";
import Breadcrumb from "@/components/common/Breadcrumb";
import { CalendarIcon, ClockIcon, MenuIcon } from "@/components/icons/Icons";
import { useGetProfileQuery } from "@/lib/api/authApi";
import ManagementBranchDropdown from "./ManagementBranchDropdown";
import ProfileDrawer from "./ProfileDrawer";

/**
 * Renders a compact informational value in the secondary navigation row.
 */
function InfoChip({ icon: Icon, children }) {
  return (
    <div className="app-panel flex min-h-9 min-w-0 items-center gap-2 rounded-lg bg-app-panel-soft px-2 py-1.5 text-[11px] text-app-muted-light sm:px-3">
      {Icon && <Icon className="size-4" />}
      {children}
    </div>
  );
}

/**
 * Renders the primary navigation, global management branch, and profile access.
 */
export default function Navbar({ onMenuClick, initialUser }) {
  const pathname = usePathname();
  const meta =
    pageMeta[pathname] || (pathname.startsWith("/management") ? null : pageMeta["/accounting"]);
  const isReports = pathname.startsWith("/reports");

  const [currentTime, setCurrentTime] = useState("");
  const [currentDate, setCurrentDate] = useState("");
  const [profileOpen, setProfileOpen] = useState(false);

  const { data: profileData } = useGetProfileQuery(undefined, {
    skip: Boolean(initialUser),
  });

  const user = profileData?.data || initialUser || {};
  const fullName = user.person?.full_name || user.username || "";
  const avatarLetter = fullName ? fullName.charAt(0).toUpperCase() : "U";

  useEffect(() => {
    /**
     * Updates the localized date and time shown below the navigation bar.
     */
    function updateDateTime() {
      const now = new Date();
      setCurrentTime(
        now
          .toLocaleTimeString("en-US", {
            hour: "numeric",
            minute: "2-digit",
            second: "2-digit",
            hour12: true,
          })
          .toLowerCase(),
      );
      setCurrentDate(
        now.toLocaleDateString("ar-EG", {
          weekday: "long",
          day: "numeric",
          month: "long",
          year: "numeric",
        }),
      );
    }

    updateDateTime();
    const timer = setInterval(updateDateTime, 1000);
    return () => clearInterval(timer);
  }, []);

  return (
    <div className="relative z-30">
      <header
        className="app-panel relative z-20 flex h-16 items-center justify-between gap-2 rounded-2xl px-3 sm:gap-4 sm:px-4"
        dir="rtl"
      >
        <div className="flex min-w-0 flex-1 items-center gap-2 sm:gap-3 md:max-w-none">
          <button
            onClick={onMenuClick}
            type="button"
            className="grid size-10 shrink-0 place-items-center rounded-xl bg-app-card-soft text-app-text border border-app-line/20 hover:border-app-yellow/50 transition lg:hidden"
            title="القائمة"
          >
            <MenuIcon className="size-6" />
          </button>
          <ManagementBranchDropdown />
        </div>

        <button
          type="button"
          onClick={() => setProfileOpen(true)}
          className="flex shrink-0 items-center gap-3 rounded-xl border border-app-line/20 bg-app-card-soft/80 p-1.5 pl-3 pr-1.5 transition duration-300 hover:border-app-yellow/50"
          aria-label="فتح الملف الشخصي"
        >
          <span className="relative grid size-9 shrink-0 place-items-center overflow-hidden rounded-full bg-app-yellow text-sm font-bold uppercase text-app-bg shadow-inner ring-1 ring-app-line/40">
            {user.person?.photo_url ? (
              <img
                src={
                  user.person.photo_url.startsWith("http")
                    ? user.person.photo_url
                    : `http://31.70.108.63/${user.person.photo_url.replace(/^\//, "")}`
                }
                className="absolute inset-0 size-full rounded-full object-cover object-center"
                alt={`صورة ${fullName || "المستخدم"}`}
              />
            ) : (
              avatarLetter
            )}
          </span>
          <span className="ml-1 hidden text-xs font-medium text-app-muted-light sm:inline">
            {fullName || "الملف الشخصي"}
          </span>
        </button>
      </header>

      <div className="relative z-10 mt-3 flex flex-wrap items-start justify-between gap-4">
        <div className="flex flex-col items-start gap-3">
          {currentTime && currentDate && (
            <div className="flex max-w-full flex-wrap gap-2 sm:gap-3">
              <InfoChip icon={CalendarIcon}>{currentDate}</InfoChip>
              <InfoChip icon={ClockIcon} dir="ltr">
                {currentTime}
              </InfoChip>
            </div>
          )}
          {meta && <Breadcrumb title={meta.title} subtitle={meta.subtitle} />}
        </div>
      </div>
      <ProfileDrawer open={profileOpen} onClose={() => setProfileOpen(false)} />
    </div>
  );
}

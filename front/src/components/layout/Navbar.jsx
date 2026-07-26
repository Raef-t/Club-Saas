"use client";

import { useState, useEffect } from "react";
import { usePathname } from "next/navigation";
import { pageMeta } from "@/data/mockData";
import Breadcrumb from "@/components/common/Breadcrumb";
import {
  SearchIcon,
  CalendarIcon,
  ClockIcon,
  MenuIcon,
} from "@/components/icons/Icons";
import { Field } from "@/components/forms/Field";
import { useGetProfileQuery } from "@/lib/api/authApi";
import ProfileDrawer from "./ProfileDrawer";

function InfoChip({ icon: Icon, children }) {
  return (
    <div className="app-panel flex min-h-9 min-w-0 items-center gap-2 rounded-lg bg-app-panel-soft px-2 py-1.5 text-[11px] text-app-muted-light sm:px-3">
      {Icon && <Icon className="size-4" />}
      {children}
    </div>
  );
}

export default function Navbar({ onMenuClick }) {
  const pathname = usePathname();
  const meta =
    pageMeta[pathname] ||
    (pathname.startsWith("/management") ? null : pageMeta["/accounting"]);
  const isReports = pathname.startsWith("/reports");

  const [currentTime, setCurrentTime] = useState("");
  const [currentDate, setCurrentDate] = useState("");
  const [profileOpen, setProfileOpen] = useState(false);

  const { data: profileData } = useGetProfileQuery();

  const user = profileData?.data || {};
  const fullName = user.person?.full_name || user.username || "";
  const avatarLetter = fullName ? fullName.charAt(0).toUpperCase() : "U";

  useEffect(() => {
    const updateDateTime = () => {
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
    };

    updateDateTime();
    const timer = setInterval(updateDateTime, 1000); // Update every second
    return () => clearInterval(timer);
  }, []);

  return (
    <div>
      <header
        className="app-panel flex h-16 items-center justify-between gap-2 rounded-2xl px-3 sm:gap-4 sm:px-4"
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
          {/* <Field
            type="search"
            placeholder="البحث"
            icon={SearchIcon}
            variant="search"
            className="w-full md:w-[373px]"
            required={false}
          /> */}
        </div>

        {/* Profile Avatar Button on the left */}
        <button
          onClick={() => setProfileOpen(true)}
          className="flex items-center gap-3 rounded-xl bg-app-card-soft/80 p-1.5 pl-3 pr-1.5 border border-app-line/20 hover:border-app-yellow/50 transition duration-300"
        >
          <div className="grid size-8 place-items-center rounded-full bg-app-yellow text-sm font-bold text-app-bg uppercase shadow-inner">
            {user.person?.photo_url ? (
              <img
                src={
                  user.person.photo_url.startsWith("http")
                    ? user.person.photo_url
                    : `http://31.70.108.63/${user.person.photo_url.replace(/^\//, "")}`
                }
                className="size-full rounded-full object-cover"
                alt="Profile Avatar"
              />
            ) : (
              avatarLetter
            )}
          </div>
          <span className="hidden sm:inline text-xs text-app-muted-light font-medium ml-1">
            {fullName || "الملف الشخصي"}
          </span>
        </button>
      </header>

      <div className="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div className="flex flex-col items-start gap-3">
          {/* We only render the time chips after mounting to avoid Next.js hydration mismatch */}
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

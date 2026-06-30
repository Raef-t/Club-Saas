"use client";

import { useState, useEffect } from "react";
import { usePathname } from "next/navigation";
import { pageMeta } from "@/data/mockData";
import Breadcrumb from "@/components/common/Breadcrumb";
import { SearchIcon, CalendarIcon, ClockIcon } from "@/components/icons/Icons";
import { Field } from "@/components/forms/Field";

function InfoChip({ icon: Icon, children }) {
  return (
    <div className="app-panel flex h-9 items-center gap-2 rounded-lg bg-app-panel-soft px-3 text-[11px] text-app-muted-light">
      {Icon && <Icon className="size-4" />}
      {children}
    </div>
  );
}

export default function Navbar() {
  const pathname = usePathname();
  const meta =
    pageMeta[pathname] || (pathname.startsWith("/management") ? null : pageMeta["/accounting"]);
  const isReports = pathname.startsWith("/reports");

  const [currentTime, setCurrentTime] = useState("");
  const [currentDate, setCurrentDate] = useState("");

  useEffect(() => {
    const updateDateTime = () => {
      const now = new Date();
      setCurrentTime(
        now
          .toLocaleTimeString("en-US", {
            hour: "numeric",
            minute: "2-digit",
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
    const timer = setInterval(updateDateTime, 60000); // Update every minute
    return () => clearInterval(timer);
  }, []);

  return (
    <div>
      <header className="app-panel flex h-16 items-center gap-4 rounded-2xl px-4">
        {/* <div className="hidden flex-1 items-center gap-14 text-app-muted-light md:flex">
          {(isReports ? [1, 2, 3] : [1]).map((item) => (
            <SearchIcon key={item} className="size-5" />
          ))}
        </div> */}

        <Field
          type="search"
          placeholder="البحث"
          icon={SearchIcon}
          variant="search"
          className="w-full md:w-[373px]"
          required={false}
        />
      </header>

      <div className="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div className="flex flex-col items-start gap-3">
          {/* We only render the time chips after mounting to avoid Next.js hydration mismatch */}
          {currentTime && currentDate && (
            <div className="flex gap-3">
              <InfoChip icon={CalendarIcon}>{currentDate}</InfoChip>
              <InfoChip icon={ClockIcon} dir="ltr">
                {currentTime}
              </InfoChip>
            </div>
          )}
          {meta && <Breadcrumb title={meta.title} subtitle={meta.subtitle} />}
        </div>
      </div>
    </div>
  );
}

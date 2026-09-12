"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  ChevronLeft,
  ClockIcon,
  DumbbellIcon,
  GridIcon,
  RefreshIcon,
  SnowflakeIcon,
  TagIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";
import BrandLogo from "@/components/common/BrandLogo";
import { usePermissions } from "@/lib/PermissionContext";

const navGroups = [
  {
    title: "التقارير العامة",
    items: [
      {
        title: "التقارير التشغيلية",
        href: "/reports",
        icon: GridIcon,
      },
    ],
  },
  {
    title: "تقارير الاشتراكات",
    items: [
      {
        title: "التقرير الشامل للاشتراكات",
        href: "/reports/subscriptions",
        icon: TagIcon,
      },
      {
        title: "حالة تجديد الاشتراكات",
        href: "/reports/renewal-status",
        icon: RefreshIcon,
      },
      {
        title: "الاشتراكات المجمدة والملغاة",
        href: "/reports/frozen-terminated",
        icon: SnowflakeIcon,
      },
    ],
  },
  {
    title: "تقارير الحصص",
    items: [
      {
        title: "سعة الحصص حسب الوقت",
        href: "/reports/time-capacity",
        icon: ClockIcon,
      },
    ],
  },
  {
    title: "تقارير الحضور",
    items: [
      {
        title: "حضور وازدحام الورديات",
        href: "/reports/shift-attendance",
        icon: ClockIcon,
      },
      {
        title: "ساعات الذروة والانخفاض",
        href: "/reports/peak-hours",
        icon: TrendUpIcon,
      },
    ],
  },
  {
    title: "تقارير الكوتشات",
    items: [
      {
        title: "اشتراكات الحصص والأجهزة",
        href: "/reports/coach-subscriptions",
        icon: DumbbellIcon,
      },
    ],
  },
];

function isRouteActive(pathname, href) {
  if (href === "/reports") {
    return pathname === "/reports";
  }
  if (href === "/reports/subscriptions") {
    return pathname === "/reports/subscriptions";
  }
  if (href === "/reports/renewal-status") {
    return (
      pathname === "/reports/renewal-status" ||
      pathname.startsWith("/reports/renewal-status/") ||
      pathname === "/reports/subscriptions/renewal-status"
    );
  }
  if (href === "/reports/frozen-terminated") {
    return (
      pathname === "/reports/frozen-terminated" ||
      pathname.startsWith("/reports/frozen-terminated/") ||
      pathname === "/reports/subscriptions/frozen-terminated"
    );
  }
  if (href === "/reports/shift-attendance") {
    return (
      pathname === "/reports/shift-attendance" ||
      pathname.startsWith("/reports/shift-attendance/") ||
      pathname === "/reports/shifts/attendance"
    );
  }
  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function ReportsSidebar({ className }) {
  const pathname = usePathname() || "";
  const { canAccess } = usePermissions();

  return (
    <aside
      className={
        className !== undefined
          ? className
          : "app-panel sticky top-6 hidden h-[calc(100vh-3rem)] overflow-y-auto overflow-x-hidden sidebar-scrollbar rounded-2xl px-1 pt-4 pb-6 lg:block"
      }
      dir="rtl"
    >
      <BrandLogo className="mx-auto h-[59px] w-[159px]" preload />

      <h3 className="mt-6 text-center text-base font-medium text-app-text">نظام التقارير</h3>

      <nav className="mx-auto mt-8 flex w-full max-w-[250px] flex-col gap-6 px-1">
        {navGroups.map((group) => {
          const visibleItems = group.items.filter((item) => canAccess(item.href));
          if (!visibleItems.length) return null;

          return (
            <div key={group.title} className="space-y-2">
              <span className="px-3 text-xs font-medium text-app-muted-light">{group.title}</span>
              <div className="flex flex-col gap-1.5">
                {visibleItems.map((item) => {
                  const active = isRouteActive(pathname, item.href);
                  const Icon = item.icon;

                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={`flex h-11 items-center justify-between rounded-lg px-3.5 text-right text-sm font-medium transition ${
                        active
                          ? "border border-app-yellow bg-app-card-hover text-app-yellow shadow-[1px_0_4px_rgba(198,161,2,0.1),inset_0_2px_3.7px_rgba(198,161,2,0.05)]"
                          : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
                      }`}
                    >
                      <div className="flex items-center gap-2.5">
                        {Icon && (
                          <Icon
                            className={`size-4.5 shrink-0 ${
                              active ? "text-app-yellow" : "text-app-muted-light"
                            }`}
                          />
                        )}
                        <span>{item.title}</span>
                      </div>
                      <ChevronLeft
                        className={`size-4 shrink-0 ${
                          active ? "text-app-yellow" : "text-app-muted-light/60"
                        }`}
                      />
                    </Link>
                  );
                })}
              </div>
            </div>
          );
        })}
      </nav>
    </aside>
  );
}

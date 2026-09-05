"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ChevronLeft } from "@/components/icons/Icons";
import BrandLogo from "@/components/common/BrandLogo";
import { usePermissions } from "@/lib/PermissionContext";

const navItems = [{ title: "التقارير التشغيلية", href: "/reports" }];

function isActive(pathname, href) {
  if (href === "/reports") return pathname === href;
  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function ReportsSidebar({ className }) {
  const pathname = usePathname() || "";
  const { canAccess } = usePermissions();
  const visibleNavItems = navItems.filter((item) => canAccess(item.href));

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

      <nav className="mx-auto mt-10 flex w-full max-w-[250px] flex-col gap-2 px-1">
        {visibleNavItems.map((item) => {
          const active = isActive(pathname, item.href);
          const hasSubItems = Boolean(
            (item.children && item.children.length > 0) ||
            (item.subItems && item.subItems.length > 0),
          );
          return (
            <Link
              key={item.title}
              href={item.href}
              className={`flex h-11 items-center ${hasSubItems ? "justify-between" : "justify-start"} rounded-lg px-5 text-right text-base transition ${active ? "border border-app-yellow bg-app-card-hover text-app-yellow shadow-[1px_0_4px_rgba(198,161,2,0.1),inset_0_2px_3.7px_rgba(198,161,2,0.05)]" : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"}`}
            >
              {hasSubItems && (
                <ChevronLeft
                  className={`size-5 ${active ? "text-app-yellow" : "text-app-muted-light"}`}
                />
              )}
              <span>{item.title}</span>
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}

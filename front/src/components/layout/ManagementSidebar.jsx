"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ChevronLeft } from "@/components/icons/Icons";
import Image from "next/image";

const navItems = [
  { title: "إدارة النادي", href: "/management/clubs" },
  { title: "الأعضاء", href: "/management/members" },
  { title: "الاشتراكات", href: "/management/subscriptions" },
  { title: "خطط الاشتراك", href: "/management/subscription-plans" },
  { title: "الفروع", href: "/management/branches" },
  { title: "المدربين", href: "/management/coaches" },
  { title: "الأنشطة الرياضية", href: "/management/activities" },
  { title: "الحضور والغياب", href: "/management/attendance" },
];

function isActive(pathname, href) {
  if (href === "/management") return pathname === href;
  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function ManagementSidebar() {
  const pathname = usePathname() || "";

  return (
    <aside
      className="app-panel sticky top-6 hidden h-[calc(100vh-3rem)] overflow-y-auto overflow-x-hidden sidebar-scrollbar rounded-2xl px-1 pt-4 pb-6 lg:block"
      dir="ltr"
    >
      <div className="mx-auto grid h-[59px] w-[159px] place-items-center">
        <Image src={"/img/logo.jpeg"} alt="Logo" width={159} height={59} />
      </div>

      <h3 className="mt-6 text-center text-base font-medium text-app-text">
        نظام الإدارة
      </h3>

      <nav className="mx-auto mt-10 flex w-[250px] flex-col gap-2">
        {navItems.map((item) => {
          const active = isActive(pathname, item.href);
          return (
            <Link
              key={item.title}
              href={item.href}
              className={`flex h-11 items-center justify-between rounded-lg px-5 text-base transition ${active ? "border border-app-yellow bg-app-card-hover text-app-yellow shadow-[1px_0_4px_rgba(198,161,2,0.1),inset_0_2px_3.7px_rgba(198,161,2,0.05)]" : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"}`}
            >
              <ChevronLeft
                className={`size-5 ${active ? "text-app-yellow" : "text-app-muted-light"}`}
              />
              <span>{item.title}</span>
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}

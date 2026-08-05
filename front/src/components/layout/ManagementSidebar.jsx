"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  ChevronLeft,
  PlusIcon,
  TagIcon,
  DumbbellIcon,
  CalendarIcon,
} from "@/components/icons/Icons";
import Image from "next/image";

const quickActions = [
  { label: "إضافة لاعب", href: "/management/members/create", icon: PlusIcon },
  { label: "فعالية", href: "/management/subscription-plans/create", icon: TagIcon },
  { label: "نشاط", href: "/management/activities/create", icon: DumbbellIcon },
  { label: "برنامج الدوام", href: "/management/schedule", icon: CalendarIcon },
];

const navGroups = [
  {
    title: "العمليات الأساسية",
    items: [
      { title: "الإحصائيات", href: "/management" },
      { title: "المدربين", href: "/management/coaches" },
      { title: "الموظفين", href: "/management/staff" },
      { title: "الأنشطة الرياضية", href: "/management/activities" },
      { title: "الفعاليات", href: "/management/subscription-plans" },
      { title: "المشتركين", href: "/management/members" },
      { title: "الاشتراكات", href: "/management/subscriptions" },
      { title: "الخزائن", href: "/management/lockers" },
      { title: "الحضور المغادرة", href: "/management/attendance" },
      { title: "جدول الدوام", href: "/management/schedule" },
    ],
  },
  {
    title: "لوحة التحكم",
    items: [
      { title: "الإعدادات", href: "/management/settings" },
      { title: "إدارة النادي", href: "/management/clubs" },
      { title: "الفروع", href: "/management/branches" },
    ],
  },
];

function isActive(pathname, href) {
  if (href === "/management") return pathname === href;
  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function ManagementSidebar({ className }) {
  const pathname = usePathname() || "";

  return (
    <aside
      className={
        className !== undefined
          ? className
          : "app-panel sticky top-6 hidden h-[calc(100vh-3rem)] overflow-y-auto overflow-x-hidden sidebar-scrollbar rounded-2xl px-1 pt-4 pb-6 lg:block"
      }
      dir="rtl"
    >
      <div className="mx-auto grid h-[59px] w-[159px] place-items-center">
        <Image src={"/img/test_logo.png"} alt="Logo" width={159} height={59} />
      </div>

      <h3 className="mt-6 text-center text-base font-medium text-app-text">إجراءات سريعة</h3>
      <div className="mt-5 grid grid-cols-4 gap-1.5 px-1 max-w-full">
        {quickActions.map((action) => {
          const Icon = action.icon;
          const actionActive = pathname === action.href;
          return (
            <Link
              key={action.label}
              href={action.href}
              className="group flex flex-col items-center gap-2 text-center text-[11px] leading-tight text-app-text"
            >
              <span
                className={`grid size-[38px] place-items-center rounded-full transition ${actionActive ? "border border-app-yellow bg-app-yellow-soft text-app-yellow" : "bg-app-card-soft text-app-muted-light group-hover:bg-app-line-soft group-hover:text-app-text"}`}
              >
                <Icon className="size-5" />
              </span>
              <span>{action.label}</span>
            </Link>
          );
        })}
      </div>

      <nav className="mx-auto mt-8 flex w-full max-w-[250px] flex-col gap-6 px-1">
        {navGroups.map((group) => (
          <div key={group.title} className="flex flex-col gap-2">
            <h4 className="px-5 text-[11px] font-bold uppercase tracking-wider text-app-muted-light/80 text-right">
              {group.title}
            </h4>
            <div className="flex flex-col gap-1.5">
              {group.items.map((item) => {
                const active = isActive(pathname, item.href);
                const hasSubItems = Boolean(
                  (item.children && item.children.length > 0) ||
                  (item.subItems && item.subItems.length > 0),
                );
                return (
                  <Link
                    key={item.title}
                    href={item.href}
                    className={`flex h-10 items-center ${hasSubItems ? "justify-between" : "justify-start"} rounded-lg px-5 text-right text-sm transition ${active ? "border border-app-yellow bg-app-card-hover text-app-yellow shadow-[1px_0_4px_rgba(198,161,2,0.1),inset_0_2px_3.7px_rgba(198,161,2,0.05)]" : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"}`}
                  >
                    {hasSubItems && (
                      <ChevronLeft
                        className={`size-4 ${active ? "text-app-yellow" : "text-app-muted-light"}`}
                      />
                    )}
                    <span>{item.title}</span>
                  </Link>
                );
              })}
            </div>
          </div>
        ))}
      </nav>
    </aside>
  );
}

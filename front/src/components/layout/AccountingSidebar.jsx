"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  ChevronLeft,
  FileDownIcon,
  FileUpIcon,
  FolderPlusIcon,
  HandCoinsIcon,
} from "@/components/icons/Icons";
import Image from "next/image";

const quickActions = [
  {
    label: "إضافة مصروف",
    href: "/accounting/expenses/create",
    icon: FileDownIcon,
  },
  {
    label: "إضافة دفعة",
    href: "/accounting/subscriptions/payments/create",
    icon: HandCoinsIcon,
  },
  {
    label: "إضافة إيراد",
    href: "/accounting/revenues/create",
    icon: FileUpIcon,
  },
  {
    label: "إضافة قيد",
    href: "/accounting/journals",
    icon: FolderPlusIcon,
  },
];

const navItems = [
  { title: "لوحة التحكم", href: "/accounting" },
  { title: "الصناديق والخزائن", href: "/accounting/safes" },
  { title: "دليل الحسابات", href: "/accounting/accounts" },
  { title: "سندات القيود", href: "/accounting/journals" },
  { title: "الإيرادات", href: "/accounting/revenues" },
  { title: "المصاريف", href: "/accounting/expenses" },
  { title: "اشتراكات الأعضاء", href: "/accounting/subscriptions" },
  { title: "الرواتب والأجور", href: "/accounting/salaries" },
  { title: "الشركاء والأرباح", href: "/accounting/partners" },
  { title: "الجهات والأطراف", href: "/accounting/counterparties" },
  { title: "الفترات المحاسبية", href: "/accounting/periods" },
  { title: "التقارير المالية", href: "/accounting/reports" },
];

function isActive(pathname, href) {
  if (href === "/accounting") return pathname === href;
  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function AccountingSidebar({ className }) {
  const pathname = usePathname();

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

      <h3 className="mt-6 text-center text-base font-medium text-app-text">
        إجراءات سريعة
      </h3>
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

      <nav className="mx-auto mt-14 flex w-full max-w-[250px] flex-col gap-2 px-1">
        {navItems.map((item) => {
          const active =
            isActive(pathname, item.href) ||
            (pathname.includes("/safes") && item.href === "/accounting/safes") ||
            (pathname.includes("/cashbox") && item.href === "/accounting/safes");
          const hasSubItems = Boolean(
            (item.children && item.children.length > 0) ||
            (item.subItems && item.subItems.length > 0)
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

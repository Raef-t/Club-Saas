"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  ChevronLeft,
  FileDownIcon,
  FileUpIcon,
  FolderPlusIcon,
  HandCoinsIcon,
  LogoMark,
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
    href: "/accounting/cashbox/create",
    icon: FolderPlusIcon,
  },
];

const navItems = [
  { title: "لوحة التحكم", href: "/accounting" },
  { title: "الصندوق", href: "/accounting/cashbox" },
  { title: "الإيرادات", href: "/accounting/revenues" },
  { title: "المصاريف", href: "/accounting/expenses" },
  { title: "اشتراكات الأعضاء", href: "/accounting/subscriptions" },
  { title: "رواتب المدربين", href: "/accounting/salaries/trainers" },
  { title: "رواتب الموظفين", href: "/accounting/salaries/employees" },
  { title: "الذمم المدينة", href: "/accounting/receivables" },
  { title: "الذمم الدائنة", href: "/accounting/debts" },
  { title: "إدارة الفروع", href: "/reports" },
];

function isActive(pathname, href) {
  if (href === "/accounting") return pathname === href;
  return pathname === href || pathname.startsWith(`${href}/`);
}

export default function AccountingSidebar() {
  const pathname = usePathname();

  return (
    <aside
      className="app-panel sticky top-6 hidden h-[calc(100vh-3rem)] overflow-y-auto overflow-x-hidden sidebar-scrollbar rounded-2xl px-1 pt-4 pb-6 lg:block"
      dir="ltr"
    >
      <div className="mx-auto grid h-[59px] w-[159px] place-items-center">
        <Image src={"/img/logo.jpeg"} alt="Logo" width={159} height={59} />
      </div>

      <h3 className="mt-6 text-center text-base font-medium text-app-text">
        إجراءات سريعة
      </h3>
      <div className="mt-5 grid grid-cols-4 gap-2 px-3">
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

      <nav className="mx-auto mt-14 flex w-[250px] flex-col gap-2">
        {navItems.map((item) => {
          const active =
            isActive(pathname, item.href) ||
            (pathname.includes("/cashbox/") &&
              item.href === "/accounting/cashbox");
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

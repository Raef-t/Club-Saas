"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { useTheme } from "next-themes";
import {
  FolderPlusIcon,
  MoonIcon,
  SignOutIcon,
  SunIcon,
  TipJarIcon,
  TrendUpIcon,
} from "@/components/icons/Icons";
import { useLogoutMutation } from "@/lib/api/authApi";
import { clearAuthStorage } from "@/lib/authStorage";

const systems = [
  {
    id: "management",
    href: "/management",
    icon: FolderPlusIcon,
    title: "إدارة النادي",
  },
  {
    id: "reports",
    href: "/reports",
    icon: TrendUpIcon,
    title: "نظام التقارير",
  },
  {
    id: "accounting",
    href: "/accounting",
    icon: TipJarIcon,
    title: "نظام المحاسبة",
  },
];

export default function IconRail() {
  const pathname = usePathname() || "";
  const router = useRouter();
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);
  const [logout, { isLoading: isLoggingOut }] = useLogoutMutation();

  useEffect(() => {
    setMounted(true);
  }, []);

  function isActive(path) {
    if (path === "#") return false;
    return pathname.startsWith(path);
  }

  async function handleLogout() {
    try {
      await logout().unwrap();
    } catch {
      // The local session should be removed even if the server token is expired.
    } finally {
      clearAuthStorage();
      router.push("/login");
    }
  }

  return (
    <aside className="app-panel sticky top-6 hidden h-[calc(100vh-3rem)] overflow-hidden rounded-2xl px-2 py-4 lg:flex lg:flex-col lg:items-center">
      <div className="mt-10 flex flex-1 flex-col items-center justify-center gap-3">
        {systems.map((sys) => {
          const Icon = sys.icon;
          const active = isActive(sys.href);

          return (
            <Link
              key={sys.id}
              href={sys.href}
              title={sys.title}
              className={`relative grid size-11 place-items-center rounded-lg transition-colors ${
                active
                  ? "yellow-glow bg-app-yellow-soft text-app-yellow"
                  : "text-app-muted-light hover:bg-app-line-soft hover:text-app-text"
              }`}
            >
              <Icon className="size-6" />
              {active && (
                <span className="absolute -start-3 top-1/2 h-9 w-1 -translate-y-1/2 rounded-full bg-gradient-to-b from-app-yellow via-app-yellow-soft to-transparent" />
              )}
            </Link>
          );
        })}
      </div>

      <div className="mb-5 grid h-[102px] w-[54px] place-items-center gap-3 rounded-lg border border-app-line text-app-muted-light">
        <button
          onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
          type="button"
          className="transition-colors hover:text-app-text"
          title="تبديل المظهر"
        >
          {mounted && theme === "light" ? (
            <SunIcon className="size-6" />
          ) : (
            <MoonIcon className="size-6" />
          )}
        </button>

        <button
          onClick={handleLogout}
          type="button"
          className="transition-colors hover:text-app-text disabled:cursor-not-allowed disabled:opacity-60"
          title="تسجيل الخروج"
          disabled={isLoggingOut}
        >
          <SignOutIcon className="size-6" />
        </button>
      </div>
    </aside>
  );
}

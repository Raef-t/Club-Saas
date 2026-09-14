"use client";

import { useRouter } from "next/navigation";
import BrandLogo from "@/components/common/BrandLogo";
import { SignOutIcon } from "@/components/icons/Icons";
import { useLogoutMutation } from "@/lib/api/authApi";
import { clearAuthStorage } from "@/lib/authStorage";

export default function PlayerAppHeader({ user }) {
  const router = useRouter();
  const [logout, { isLoading: isLoggingOut }] = useLogoutMutation();

  async function handleLogout() {
    try {
      await logout().unwrap();
    } catch {
      // Local session should be removed even if server token is expired
    } finally {
      clearAuthStorage();
      router.replace("/login");
      router.refresh();
    }
  }

  const displayName =
    user?.full_name ||
    user?.person?.full_name ||
    user?.name ||
    user?.username ||
    "المشترك";

  return (
    <header className="sticky top-0 z-30 mb-6 border-b border-app-line/60 bg-app-card/85 backdrop-blur-md">
      <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
        <div className="flex items-center gap-3">
          <BrandLogo className="h-10 w-28 sm:h-12 sm:w-32" />
        </div>

        <div className="flex items-center gap-3">
          <div className="text-left">
            <p className="text-xs sm:text-sm font-semibold text-white">{displayName}</p>
          </div>

          <button
            type="button"
            onClick={handleLogout}
            disabled={isLoggingOut}
            className="flex items-center gap-1.5 rounded-xl border border-app-line bg-app-card-soft px-3 py-2 text-xs font-medium text-app-muted-light transition hover:border-app-red/50 hover:bg-app-red/10 hover:text-app-red disabled:opacity-50"
            title="تسجيل الخروج"
          >
            <SignOutIcon className="size-4" />
            <span>خروج</span>
          </button>
        </div>
      </div>
    </header>
  );
}

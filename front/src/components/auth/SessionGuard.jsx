"use client";

import { useCallback, useEffect, useState } from "react";
import { useGetProfileQuery } from "@/lib/api/authApi";
import { clearAuthStorage } from "@/lib/authStorage";
import LoadingSpinner from "@/components/ui/LoadingSpinner";

function SessionLoading() {
  return (
    <div className="dashboard-bg grid min-h-screen place-items-center text-app-yellow">
      <LoadingSpinner className="size-8" label="جاري التحقق من الجلسة" />
    </div>
  );
}

export default function SessionGuard({ children }) {
  const [isRestoring, setIsRestoring] = useState(false);
  const {
    isLoading,
    isError,
    error,
    refetch,
  } = useGetProfileQuery();

  const redirectToLogin = useCallback(() => {
    clearAuthStorage();
    window.location.replace("/login");
  }, []);

  useEffect(() => {
    if (isError && error?.status === 401) {
      redirectToLogin();
    }
  }, [error, isError, redirectToLogin]);

  useEffect(() => {
    async function validateRestoredPage(event) {
      if (!event.persisted) return;

      setIsRestoring(true);

      try {
        await refetch().unwrap();
      } catch (refetchError) {
        if (refetchError?.status === 401) {
          redirectToLogin();
          return;
        }
      } finally {
        setIsRestoring(false);
      }
    }

    window.addEventListener("pageshow", validateRestoredPage);
    return () => window.removeEventListener("pageshow", validateRestoredPage);
  }, [redirectToLogin, refetch]);

  if (isLoading || isRestoring || (isError && error?.status === 401)) {
    return <SessionLoading />;
  }

  if (isError) {
    return (
      <main className="dashboard-bg grid min-h-screen place-items-center px-4">
        <section className="card-shell max-w-md rounded-3xl p-8 text-center">
          <h1 className="text-xl font-semibold text-white">
            تعذر التحقق من الجلسة
          </h1>
          <p className="mt-3 text-sm text-app-muted-light">
            تحقق من اتصال الخادم ثم أعد المحاولة.
          </p>
          <button
            type="button"
            onClick={() => refetch()}
            className="mt-6 h-11 rounded-xl bg-app-yellow px-6 font-medium text-app-bg"
          >
            إعادة المحاولة
          </button>
        </section>
      </main>
    );
  }

  return children;
}

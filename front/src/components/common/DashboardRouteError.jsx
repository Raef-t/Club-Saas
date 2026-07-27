"use client";

import { useEffect } from "react";

/**
 * Displays a recoverable error boundary for dashboard route segments.
 *
 * @param {{error: Error, reset: () => void}} props Error boundary properties.
 */
export default function DashboardRouteError({ error, reset }) {
  useEffect(() => {
    console.error("Dashboard route error:", error);
  }, [error]);

  return (
    <main className="dashboard-bg grid min-h-[60vh] place-items-center px-4">
      <section className="card-shell max-w-md rounded-3xl p-8 text-center">
        <h1 className="text-xl font-semibold text-white">تعذر تحميل الصفحة</h1>
        <p className="mt-3 text-sm text-app-muted-light">تحقق من اتصال الخادم ثم أعد المحاولة.</p>
        <button
          type="button"
          onClick={reset}
          className="mt-6 h-11 rounded-xl bg-app-yellow px-6 font-medium text-app-bg"
        >
          إعادة المحاولة
        </button>
      </section>
    </main>
  );
}

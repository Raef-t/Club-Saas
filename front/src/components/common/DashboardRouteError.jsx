"use client";

import { useEffect } from "react";

/**
 * Displays a recoverable error boundary for dashboard route segments.
 *
 * @param {{error: Error, unstable_retry?: () => void, reset?: () => void}} props Error boundary properties.
 */
export default function DashboardRouteError({ error, unstable_retry, reset }) {
  const retry = unstable_retry || reset;
  const isChunkLoadError = /chunkloaderror|loading chunk/i.test(
    `${error?.name || ""} ${error?.message || ""}`,
  );
  const isBackendUnavailable = /Backend request failed with status (502|503|504)/i.test(
    error?.message || "",
  );

  useEffect(() => {
    console.error("Dashboard route error:", error);
  }, [error]);

  function handleRetry() {
    if (isChunkLoadError && typeof window !== "undefined") {
      window.location.reload();
      return;
    }
    retry?.();
  }

  return (
    <main className="dashboard-bg grid min-h-[60vh] place-items-center px-4">
      <section className="card-shell max-w-md rounded-3xl p-8 text-center">
        <h1 className="text-xl font-semibold text-white">
          {isChunkLoadError
            ? "تم تحديث ملفات النظام"
            : isBackendUnavailable
              ? "الخادم غير متاح مؤقتاً"
              : "تعذر تحميل الصفحة"}
        </h1>
        <p className="mt-3 text-sm text-app-muted-light">
          {isChunkLoadError
            ? "تم تحديث حزم وملفات الصفحة في بيئة العمل. يرجى الضغط على الزر أدناه لإعادة تحميل الصفحة."
            : isBackendUnavailable
              ? "لم يستجب الخادم الخلفي في الوقت المحدد. انتظر قليلاً ثم أعد المحاولة."
              : "حدث خطأ غير متوقع أثناء تحميل الصفحة. أعد المحاولة بعد قليل."}
        </p>
        <button
          type="button"
          onClick={handleRetry}
          className="mt-6 h-11 rounded-xl bg-app-yellow px-6 font-medium text-app-bg transition hover:opacity-90 cursor-pointer"
        >
          {isChunkLoadError ? "تحديث الصفحة الآن" : "إعادة المحاولة"}
        </button>
      </section>
    </main>
  );
}

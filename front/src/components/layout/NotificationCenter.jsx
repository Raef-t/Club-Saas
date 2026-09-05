"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { BellIcon, HandCoinsIcon, XIcon } from "@/components/icons/Icons";
import { useGetNotificationsQuery } from "@/lib/api/notificationsApi";
import {
  getNotificationAction,
  getNotifications,
  getUnreadNotificationsCount,
} from "@/lib/notifications";
import { useOptionalManagementBranch } from "@/lib/ManagementBranchContext";

export default function NotificationCenter() {
  const router = useRouter();
  const branchContext = useOptionalManagementBranch();
  const containerRef = useRef(null);
  const [open, setOpen] = useState(false);
  const [minimizedNotificationId, setMinimizedNotificationId] = useState(null);
  const [handledNotificationId, setHandledNotificationId] = useState(null);
  const { data, isLoading, isFetching, error, refetch } = useGetNotificationsQuery(undefined, {
    pollingInterval: 30_000,
    refetchOnFocus: true,
  });
  const notifications = useMemo(() => getNotifications(data), [data]);
  const unreadCount = getUnreadNotificationsCount(notifications);
  const floatingNotification = useMemo(
    () =>
      notifications.find(
        (notification) => !notification?.is_read && getNotificationAction(notification),
      ) || null,
    [notifications],
  );
  const floatingNotificationId = floatingNotification
    ? getNotificationKey(floatingNotification)
    : null;
  const showFloatingNotification =
    floatingNotification && floatingNotificationId !== handledNotificationId;
  const isFloatingMinimized = floatingNotificationId === minimizedNotificationId;

  useEffect(() => {
    if (!open) return undefined;

    function closeOnOutsideClick(event) {
      if (!containerRef.current?.contains(event.target)) setOpen(false);
    }

    function closeOnEscape(event) {
      if (event.key === "Escape") setOpen(false);
    }

    document.addEventListener("pointerdown", closeOnOutsideClick);
    document.addEventListener("keydown", closeOnEscape);
    return () => {
      document.removeEventListener("pointerdown", closeOnOutsideClick);
      document.removeEventListener("keydown", closeOnEscape);
    };
  }, [open]);

  function handleNotificationClick(notification) {
    const action = getNotificationAction(notification);
    if (!action) return;

    setHandledNotificationId(getNotificationKey(notification));
    branchContext?.setSelectedBranchId?.(action.branchId);
    setOpen(false);
    router.push(action.href);
  }

  return (
    <>
      <div className="relative" ref={containerRef}>
        <button
          type="button"
          className="relative grid size-11 place-items-center rounded-xl border border-app-line/20 bg-app-card-soft/80 text-app-muted-light transition hover:border-app-yellow/50 hover:text-app-yellow"
          onClick={() => setOpen((current) => !current)}
          aria-label={`الإشعارات${unreadCount ? `، ${unreadCount} غير مقروء` : ""}`}
          aria-expanded={open}
        >
          <BellIcon className="size-5" />
          {unreadCount > 0 && (
            <span className="absolute -start-1 -top-1 grid min-h-5 min-w-5 place-items-center rounded-full border-2 border-app-panel bg-app-red px-1 text-[9px] font-bold leading-none text-white">
              {unreadCount > 99 ? "+99" : unreadCount.toLocaleString("ar")}
            </span>
          )}
        </button>

        {open && (
          <section
            className="absolute end-0 top-[calc(100%+0.75rem)] z-50 w-[390px] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-app-line bg-app-panel shadow-2xl"
            aria-label="قائمة الإشعارات"
          >
            <header className="flex items-center justify-between border-b border-app-line px-4 py-3">
              <div>
                <h2 className="text-sm font-semibold text-app-text">الإشعارات</h2>
                <p className="mt-0.5 text-[11px] text-app-muted-light">
                  {isFetching && !isLoading
                    ? "جاري التحديث..."
                    : unreadCount
                      ? `${unreadCount.toLocaleString("ar")} إشعار غير مقروء`
                      : "لا توجد إشعارات جديدة"}
                </p>
              </div>
              <button
                type="button"
                className="grid size-8 place-items-center rounded-lg text-app-muted-light transition hover:bg-app-card-soft hover:text-app-text"
                onClick={() => setOpen(false)}
                aria-label="إغلاق الإشعارات"
              >
                <XIcon className="size-4" />
              </button>
            </header>

            <div className="max-h-[min(520px,70vh)] overflow-y-auto p-2">
              {isLoading ? (
                <NotificationSkeleton />
              ) : error ? (
                <div className="px-4 py-8 text-center">
                  <p className="text-xs text-app-red">تعذر تحميل الإشعارات.</p>
                  <button
                    type="button"
                    className="mt-3 text-xs font-medium text-app-yellow underline underline-offset-4"
                    onClick={refetch}
                  >
                    إعادة المحاولة
                  </button>
                </div>
              ) : notifications.length === 0 ? (
                <p className="px-4 py-10 text-center text-xs text-app-muted-light">
                  لا توجد إشعارات حالياً.
                </p>
              ) : (
                notifications.map((notification, index) => {
                  const action = getNotificationAction(notification);
                  const key = notification.notification_id || notification.recipient_id || index;

                  return (
                    <button
                      key={key}
                      type="button"
                      className={`relative flex w-full items-start gap-3 rounded-xl px-3 py-3 text-right transition ${
                        action ? "hover:bg-app-card-soft" : "cursor-default opacity-80"
                      } ${notification.is_read ? "" : "bg-app-yellow/[0.04]"}`}
                      onClick={() => handleNotificationClick(notification)}
                      disabled={!action}
                    >
                      {!notification.is_read && (
                        <span className="absolute end-1.5 top-1/2 size-1.5 -translate-y-1/2 rounded-full bg-app-yellow" />
                      )}
                      <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-app-yellow/10 text-app-yellow">
                        <HandCoinsIcon className="size-5" />
                      </span>
                      <span className="min-w-0 flex-1 pe-1">
                        <strong className="block text-xs font-medium leading-5 text-app-text">
                          {notification.title || "إشعار جديد"}
                        </strong>
                        {notification.preview && (
                          <span className="mt-1 line-clamp-2 block text-[11px] leading-5 text-app-muted-light">
                            {notification.preview}
                          </span>
                        )}
                        <span className="mt-1.5 block text-[10px] text-app-muted-light/75">
                          {notification.created_at_human || ""}
                        </span>
                      </span>
                    </button>
                  );
                })
              )}
            </div>
          </section>
        )}
      </div>

      {showFloatingNotification && (
        <FloatingPayrollNotification
          notification={floatingNotification}
          minimized={isFloatingMinimized}
          onMinimize={() => setMinimizedNotificationId(floatingNotificationId)}
          onAction={() => handleNotificationClick(floatingNotification)}
        />
      )}
    </>
  );
}

function FloatingPayrollNotification({ notification, minimized, onMinimize, onAction }) {
  if (minimized) {
    return (
      <button
        type="button"
        className="fixed bottom-5 left-5 z-[80] grid size-14 place-items-center rounded-full border border-app-yellow/40 bg-app-card text-app-yellow shadow-[0_14px_40px_rgba(0,0,0,0.38)] transition hover:-translate-y-0.5 hover:border-app-yellow"
        onClick={onAction}
        aria-label="توليد مسودة الرواتب من الإشعار"
        title="اضغط لتوليد مسودة الرواتب"
      >
        <HandCoinsIcon className="size-6" />
        <span className="absolute end-0 top-0 size-3 rounded-full border-2 border-app-card bg-app-red" />
      </button>
    );
  }

  return (
    <aside
      className="fixed bottom-5 left-5 z-[80] w-[370px] max-w-[calc(100vw-2.5rem)] overflow-hidden rounded-2xl border border-app-yellow/35 bg-app-card shadow-[0_18px_55px_rgba(0,0,0,0.42)] animate-fade-in"
      aria-label="إشعار استحقاق الرواتب"
      dir="rtl"
    >
      <button
        type="button"
        className="absolute left-3 top-3 z-10 grid size-8 place-items-center rounded-lg border border-app-line bg-app-panel-soft text-app-muted-light transition hover:border-app-yellow/50 hover:text-app-yellow"
        onClick={onMinimize}
        aria-label="تصغير الإشعار إلى دائرة"
        title="تصغير"
      >
        <MinimizeIcon className="size-4" />
      </button>

      <button
        type="button"
        className="flex w-full items-start gap-3 p-4 pl-12 text-right transition hover:bg-app-yellow/[0.04]"
        onClick={onAction}
        aria-label="توليد مسودة الرواتب من هذا الإشعار"
      >
        <span className="grid size-11 shrink-0 place-items-center rounded-full bg-app-yellow/12 text-app-yellow">
          <HandCoinsIcon className="size-6" />
        </span>
        <span className="min-w-0 flex-1">
          <strong className="block pe-1 text-sm font-semibold leading-6 text-app-text">
            {notification.title || "حان موعد توليد الرواتب"}
          </strong>
          {notification.preview && (
            <span className="mt-1 line-clamp-2 block text-xs leading-5 text-app-muted-light">
              {notification.preview}
            </span>
          )}
          <span className="mt-2 flex items-center justify-between gap-3 text-[11px]">
            <span className="font-medium text-app-yellow">اضغط لتوليد مسودة الرواتب</span>
            <span className="shrink-0 text-app-muted-light/75">
              {notification.created_at_human || ""}
            </span>
          </span>
        </span>
      </button>
    </aside>
  );
}

function getNotificationKey(notification) {
  return String(
    notification?.notification_id ??
      notification?.recipient_id ??
      `${notification?.target_snapshot?.branch_id || "payroll"}-${notification?.created_at || "due"}`,
  );
}

function MinimizeIcon({ className = "size-4" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      aria-hidden="true"
    >
      <path d="M5 12h14" strokeLinecap="round" />
    </svg>
  );
}

function NotificationSkeleton() {
  return (
    <div className="space-y-2 p-1" role="status" aria-label="جاري تحميل الإشعارات">
      {[1, 2, 3].map((item) => (
        <div key={item} className="flex animate-pulse gap-3 rounded-xl p-3">
          <span className="size-10 shrink-0 rounded-xl bg-app-card-soft" />
          <span className="flex-1 space-y-2 py-1">
            <span className="block h-3 w-2/3 rounded bg-app-card-soft" />
            <span className="block h-2.5 w-full rounded bg-app-card-soft" />
          </span>
        </div>
      ))}
    </div>
  );
}

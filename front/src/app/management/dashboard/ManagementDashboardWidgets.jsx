import Link from "next/link";
import { useState } from "react";
import { GridIcon } from "@/components/icons/Icons";
import { useTimeFormat } from "@/lib/TimeFormatContext";

const QUICK_LINKS = [
  { title: "الأعضاء", href: "/management/members" },
  { title: "المدربون", href: "/management/coaches" },
  { title: "الاشتراكات", href: "/management/subscriptions" },
  { title: "الفعاليات", href: "/management/subscription-plans" },
  { title: "الأنشطة الرياضية", href: "/management/activities" },
  { title: "الحضور والغياب", href: "/management/attendance" },
  { title: "جدول الدوام", href: "/management/schedule" },
  { title: "الخزائن", href: "/management/lockers" },
  { title: "الفروع", href: "/management/branches" },
  { title: "إدارة النادي", href: "/management/clubs" },
  { title: "الإعدادات", href: "/management/settings" },
];

const statusClasses = {
  green: "bg-app-green/20 text-app-green",
  yellow: "bg-app-yellow/20 text-app-yellow",
  neutral: "bg-app-line-soft text-app-muted-light",
};

/**
 * Renders the subscription distribution as a labeled donut.
 */
export function SubscriptionDonut({ items }) {
  const total = items.reduce((sum, item) => sum + item.value, 0);
  let offset = 25;

  if (!items.length) {
    return (
      <p className="grid min-h-36 place-items-center px-5 pb-5 text-sm text-app-muted">
        لا توجد اشتراكات لعرض توزيعها.
      </p>
    );
  }

  return (
    <div className="flex items-center justify-center gap-8 px-5 pb-5 pt-1">
      <ul className="space-y-3 text-sm text-app-text">
        {items.map((item) => (
          <li key={item.label} className="flex items-center justify-start gap-3">
            <span className="max-w-28 truncate">{item.label}</span>
            <span className="size-2.5 rounded-full" style={{ backgroundColor: item.color }} />
          </li>
        ))}
      </ul>
      <svg className="size-36 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
        <circle cx="60" cy="60" r="38" fill="none" stroke="#222" strokeWidth="18" />
        {items.map((item) => {
          const length = total ? (item.value / total) * 239 : 0;
          const segment = (
            <circle
              key={item.label}
              cx="60"
              cy="60"
              r="38"
              fill="none"
              stroke={item.color}
              strokeWidth="18"
              strokeDasharray={`${length} ${239 - length}`}
              strokeDashoffset={-offset}
            />
          );
          offset += length;
          return segment;
        })}
      </svg>
    </div>
  );
}

/**
 * Renders coach subscriptions as an interactive, scrollable donut.
 */
export function CoachSubscriptionsDonut({ items = [], isLoading = false, hasError = false }) {
  const [activeCoachId, setActiveCoachId] = useState(null);
  const total = items.reduce((sum, item) => sum + item.value, 0);
  const activeItem = items.find((item) => item.id === activeCoachId) || null;
  const radius = 43;
  const circumference = 2 * Math.PI * radius;
  let offset = 0;

  if (isLoading) {
    return (
      <div
        className="grid min-h-44 place-items-center px-5 pb-5 text-sm text-app-muted"
        role="status"
      >
        جاري تحميل إحصائيات الكوتشات...
      </div>
    );
  }

  if (!items.length) {
    return (
      <p
        className="grid min-h-56 place-items-center px-5 pb-5 text-center text-sm text-app-muted"
        role={hasError ? "alert" : undefined}
      >
        {hasError
          ? "تعذر تحميل إحصائيات اشتراكات الكوتشات."
          : "لا توجد اشتراكات نشطة للكوتشات في الفرع المختار."}
      </p>
    );
  }

  return (
    <div className="flex min-h-64 flex-col items-center px-4 pb-4 pt-3 sm:px-5">
      <div
        className="flex h-14 w-full shrink-0 items-center justify-center overflow-hidden pb-1"
        data-testid="coach-tooltip-slot"
      >
        <div
          className={`max-w-full rounded-xl border bg-app-panel px-4 py-2 text-center transition-[opacity,transform,border-color,box-shadow] duration-200 ease-out motion-reduce:transition-none ${
            activeItem
              ? "translate-y-0 border-app-yellow/30 opacity-100 shadow-xl"
              : "pointer-events-none translate-y-1 border-transparent opacity-0 shadow-none"
          }`}
          role="tooltip"
          aria-hidden={!activeItem}
        >
          <div className="flex items-center justify-center gap-2 text-sm font-semibold text-app-text">
            <span
              className="size-2.5 shrink-0 rounded-full transition-colors duration-200"
              style={{ backgroundColor: activeItem?.color || "transparent" }}
            />
            <span className="truncate">{activeItem?.label || "الكوتش"}</span>
            <span className="shrink-0 text-app-yellow">
              {(activeItem?.value || 0).toLocaleString("ar")} لاعب
            </span>
          </div>
          <p
            className={`mt-1 truncate text-[11px] text-app-muted-light ${
              activeItem?.activities?.length ? "visible" : "invisible"
            }`}
          >
            {activeItem?.activities?.join("، ") || "لا توجد فعاليات"}
          </p>
        </div>
      </div>

      <div className="relative size-50 shrink-0 sm:size-52">
        <svg
          className="size-full"
          viewBox="0 0 120 120"
          role="img"
          aria-label="توزيع اللاعبين النشطين حسب الكوتش"
        >
          <g transform="rotate(-90 60 60)">
            <circle cx="60" cy="60" r={radius} fill="none" stroke="#222" strokeWidth="12" />
            {items.map((item) => {
              const length = total > 0 ? (item.value / total) * circumference : 0;
              const segmentOffset = offset;
              offset += length;

              return (
                <circle
                  key={item.id}
                  cx="60"
                  cy="60"
                  r={radius}
                  fill="none"
                  stroke={item.color}
                  strokeWidth="12"
                  strokeDasharray={`${length} ${circumference - length}`}
                  strokeDashoffset={-segmentOffset}
                  tabIndex={0}
                  className={`cursor-pointer outline-none transition-[opacity,filter] duration-200 ease-out motion-reduce:transition-none hover:brightness-125 ${
                    activeItem && activeItem.id !== item.id
                      ? "opacity-25"
                      : "opacity-100 focus-visible:brightness-125"
                  }`}
                  style={{
                    filter:
                      activeItem?.id === item.id ? `drop-shadow(0 0 3px ${item.color})` : "none",
                  }}
                  onMouseEnter={() => setActiveCoachId(item.id)}
                  onMouseLeave={() => setActiveCoachId(null)}
                  onFocus={() => setActiveCoachId(item.id)}
                  onBlur={() => setActiveCoachId(null)}
                  aria-label={`${item.label}: ${item.value.toLocaleString("ar")} لاعب`}
                />
              );
            })}
          </g>
        </svg>

        <div
          className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-5 text-center"
          aria-live="polite"
        >
          <strong className="text-2xl text-app-text">
            {(activeItem?.value ?? total).toLocaleString("ar")}
          </strong>
          <span className="mt-0.5 max-w-24 truncate text-[11px] text-app-muted">
            {activeItem?.label || "إجمالي اللاعبين"}
          </span>
        </div>
      </div>

      <div className="mt-2 w-full max-h-44 overflow-y-auto px-0.5 pt-1">
        <ul className="grid grid-cols-2 gap-x-2 gap-y-1.5 text-sm text-app-text sm:grid-cols-3">
          {items.map((item) => {
            const activities = item.activities.join("، ");
            const details = activities ? `${item.label} — ${activities}` : item.label;

            return (
              <li key={item.id} className="min-w-0">
                <button
                  type="button"
                  className={`flex h-10 w-full items-center justify-between gap-1.5 rounded-lg border px-3 text-xs text-start transition-[opacity,transform,background-color,border-color,box-shadow] duration-200 ease-out motion-safe:hover:-translate-y-0.5 motion-reduce:transition-none ${
                    activeItem && activeItem.id !== item.id
                      ? "border-app-line/50 bg-black/10 opacity-40"
                      : "border-app-line bg-black/20 hover:border-app-yellow/40 hover:bg-white/5 hover:shadow-[0_6px_18px_-10px_rgba(242,220,46,0.55)] focus-visible:border-app-yellow/50"
                  }`}
                  title={`${details}: ${item.value.toLocaleString("ar")} لاعب`}
                  onMouseEnter={() => setActiveCoachId(item.id)}
                  onMouseLeave={() => setActiveCoachId(null)}
                  onFocus={() => setActiveCoachId(item.id)}
                  onBlur={() => setActiveCoachId(null)}
                >
                  <span className="flex items-center gap-1.5 min-w-0 truncate">
                    <span
                      className="size-2 shrink-0 rounded-full"
                      style={{ backgroundColor: item.color }}
                    />
                    <span className="truncate text-app-text">{item.label}</span>
                  </span>
                  <span className="text-xs font-semibold text-app-yellow shrink-0">
                    {item.value.toLocaleString("ar")}
                  </span>
                </button>
              </li>
            );
          })}
        </ul>
      </div>
    </div>
  );
}

/**
 * Renders today's schedule as a responsive data table.
 */
export function DailyScheduleTable({ sessions }) {
  const { formatTime } = useTimeFormat();

  if (!sessions.length) {
    return (
      <div className="grid min-h-44 place-items-center rounded-xl border border-dashed border-app-line px-5 text-center text-sm text-app-muted">
        لا توجد حصص مجدولة لهذا اليوم في الفرع المختار.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[820px] border-separate border-spacing-y-2 text-right text-sm">
        <thead className="text-xs text-app-muted-light">
          <tr>
            <th className="px-4 pb-1 font-medium">الوقت</th>
            <th className="px-4 pb-1 font-medium">الحصة أو الفعالية</th>
            <th className="px-4 pb-1 font-medium">المدرب</th>
            <th className="px-4 pb-1 font-medium">الفرع</th>
            <th className="px-4 pb-1 font-medium">اللاعبون الحاضرون</th>
            <th className="px-4 pb-1 font-medium">الحالة</th>
          </tr>
        </thead>
        <tbody>
          {sessions.map((session) => (
            <tr
              key={session.id}
              className={`bg-app-card-soft transition-opacity duration-300 ${
                session.status.tone === "yellow" ? "opacity-45 hover:opacity-100" : ""
              }`}
            >
              <td className="rounded-s-xl px-4 py-3 font-medium text-app-yellow" dir="ltr">
                {formatTime(session.startTime)} - {formatTime(session.endTime)}
              </td>
              <td className="px-4 py-3 text-app-text">{session.title}</td>
              <td className="px-4 py-3 text-app-muted-light">{session.coach}</td>
              <td className="px-4 py-3 text-app-muted-light">{session.branch}</td>
              <td className="px-4 py-3">
                {session.presentPlayersCount === null ? (
                  <span
                    className="inline-block h-6 w-20 animate-pulse rounded-lg bg-app-line-soft"
                    role="status"
                    aria-label="جاري تحميل عدد اللاعبين الحاضرين"
                  />
                ) : (
                  <span className="inline-flex min-w-20 items-center justify-center rounded-lg border border-app-line bg-black/30 px-3 py-1 text-xs font-medium text-app-yellow">
                    {session.presentPlayersCount.toLocaleString("ar")} لاعب
                  </span>
                )}
              </td>
              <td className="rounded-e-xl px-4 py-3">
                <span
                  className={`inline-flex min-w-16 items-center justify-center gap-1.5 rounded-lg px-3 py-1 text-xs ${
                    statusClasses[session.status.tone] || statusClasses.neutral
                  }`}
                >
                  {session.status.tone === "green" && (
                    <span className="relative flex h-2 w-2">
                      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-app-green opacity-75"></span>
                      <span className="relative inline-flex rounded-full h-2 w-2 bg-app-green"></span>
                    </span>
                  )}
                  {session.status.label}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

/**
 * Links the statistics dashboard to every management page.
 */
export function DashboardQuickLinks() {
  return (
    <div className="grid grid-cols-[repeat(auto-fit,minmax(min(100%,150px),1fr))] gap-3">
      {QUICK_LINKS.map((item) => (
        <Link
          key={item.href}
          href={item.href}
          className="flex min-h-14 items-center justify-start gap-3 rounded-xl border border-app-line bg-app-card-soft px-4 text-sm text-app-text transition hover:border-app-yellow/60 hover:text-app-yellow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow/60"
        >
          <GridIcon className="size-4 shrink-0 text-app-yellow" />
          <span className="truncate">{item.title}</span>
        </Link>
      ))}
    </div>
  );
}

/**
 * Renders a compact link inside a dashboard section header.
 */
export function DashboardSectionLink({ href, children = "عرض الكل" }) {
  return (
    <Link
      href={href}
      className="transition hover:text-app-yellow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow/60"
    >
      {children}
    </Link>
  );
}

/**
 * Renders currently active session plans (live SSE stream data).
 */
export function CurrentActiveSessionsTable({ sessions = [] }) {
  const { formatTime } = useTimeFormat();

  if (!sessions || !sessions.length) {
    return (
      <div className="grid min-h-36 place-items-center rounded-xl border border-dashed border-app-line px-5 text-center text-sm text-app-muted">
        لا توجد فعاليات تجري حالياً في الوقت الحالي.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[650px] border-separate border-spacing-y-2 text-right text-sm">
        <thead className="text-xs text-app-muted-light">
          <tr>
            <th className="px-4 pb-1 font-medium">اسم الفعالية / الخطة</th>
            <th className="px-4 pb-1 font-medium">الوقت</th>
            <th className="px-4 pb-1 font-medium">اللاعبين المتواجدين</th>
            <th className="px-4 pb-1 font-medium">الحالة الحالية</th>
          </tr>
        </thead>
        <tbody>
          {sessions.map((session, index) => {
            const planName =
              typeof session.plan_name === "object"
                ? session.plan_name?.ar || session.plan_name?.en || "-"
                : session.plan_name || "-";
            const startTime = session.start_time ? formatTime(session.start_time) : "-";
            const endTime = session.end_time ? formatTime(session.end_time) : "-";

            return (
              <tr
                key={session.plan_id || session.session_template_id || index}
                className="bg-app-card-soft"
              >
                <td className="rounded-s-xl px-4 py-3 font-medium text-app-text">{planName}</td>
                <td className="px-4 py-3 text-app-yellow font-medium" dir="ltr">
                  {startTime} - {endTime}
                </td>
                <td className="px-4 py-3 text-white font-semibold">
                  <span className="inline-flex items-center gap-1.5 rounded-lg bg-black/30 px-3 py-1 text-xs border border-app-line text-app-yellow font-medium">
                    {session.present_players_count || 0} لاعب متواجد
                  </span>
                </td>
                <td className="rounded-e-xl px-4 py-3">
                  <span className="inline-flex items-center gap-2 rounded-lg bg-app-green/20 px-3 py-1 text-xs text-app-green font-medium">
                    <span className="relative flex size-2">
                      <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-app-green opacity-75"></span>
                      <span className="relative inline-flex size-2 rounded-full bg-app-green"></span>
                    </span>
                    جارية الآن
                  </span>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

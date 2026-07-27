import Link from "next/link";
import { GridIcon } from "@/components/icons/Icons";

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
          <li key={item.label} className="flex items-center justify-end gap-3">
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
 * Renders today's schedule as a responsive data table.
 */
export function DailyScheduleTable({ sessions }) {
  if (!sessions.length) {
    return (
      <div className="grid min-h-44 place-items-center rounded-xl border border-dashed border-app-line px-5 text-center text-sm text-app-muted">
        لا توجد حصص مجدولة لهذا اليوم في الفرع المختار.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[700px] border-separate border-spacing-y-2 text-right text-sm">
        <thead className="text-xs text-app-muted-light">
          <tr>
            <th className="px-4 pb-1 font-medium">الوقت</th>
            <th className="px-4 pb-1 font-medium">الحصة أو الفعالية</th>
            <th className="px-4 pb-1 font-medium">المدرب</th>
            <th className="px-4 pb-1 font-medium">الفرع</th>
            <th className="px-4 pb-1 font-medium">الحالة</th>
          </tr>
        </thead>
        <tbody>
          {sessions.map((session) => (
            <tr key={session.id} className="bg-app-card-soft">
              <td className="rounded-r-xl px-4 py-3 font-medium text-app-yellow" dir="ltr">
                {session.startTime} - {session.endTime}
              </td>
              <td className="px-4 py-3 text-app-text">{session.title}</td>
              <td className="px-4 py-3 text-app-muted-light">{session.coach}</td>
              <td className="px-4 py-3 text-app-muted-light">{session.branch}</td>
              <td className="rounded-l-xl px-4 py-3">
                <span
                  className={`inline-flex min-w-16 justify-center rounded-lg px-3 py-1 text-xs ${
                    statusClasses[session.status.tone] || statusClasses.neutral
                  }`}
                >
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

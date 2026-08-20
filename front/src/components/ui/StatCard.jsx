import Link from "next/link";
import {
  CalendarIcon,
  ClockIcon,
  DumbbellIcon,
  GiftIcon,
  GridIcon,
  SealCheckIcon,
  TagIcon,
  TrendUpIcon,
  UsersIcon,
} from "@/components/icons/Icons";

const statIcons = {
  activities: GiftIcon,
  coaches: DumbbellIcon,
  expiring: ClockIcon,
  generalTraining: DumbbellIcon,
  members: UsersIcon,
  privateTraining: SealCheckIcon,
  schedule: CalendarIcon,
  subscriptions: TagIcon,
};

const toneMap = {
  yellow: {
    icon: "bg-app-yellow-soft text-app-yellow",
    spark: "#F2DC2E",
  },
  green: {
    icon: "bg-[rgba(19,172,73,0.22)] text-app-green",
    spark: "#13AC49",
  },
  blue: {
    icon: "bg-[rgba(7,85,255,0.22)] text-app-blue",
    spark: "#0755FF",
  },
  purple: {
    icon: "bg-[rgba(121,37,255,0.22)] text-app-purple",
    spark: "#7925FF",
  },
  orange: {
    icon: "bg-[rgba(179,107,0,0.24)] text-app-orange",
    spark: "#B36B00",
  },
  cyan: {
    icon: "bg-[rgba(0,188,212,0.22)] text-cyan-400",
    spark: "#00BCD4",
  },
};

function MiniSpark({ tone = "yellow" }) {
  const color = toneMap[tone]?.spark || toneMap.yellow.spark;

  return (
    <svg
      className="h-[23px] w-[57px] overflow-visible"
      viewBox="0 0 57 23"
      fill="none"
      aria-hidden="true"
    >
      <path
        d="M1 19.5L8 12.5L14 16.5L22 8.5L30 12.5L39 7L48 10L56 3"
        stroke={color}
        strokeWidth="1.7"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <path
        d="M1 22L8 15L14 19L22 11L30 15L39 9.5L48 12.5L56 5.5V22H1Z"
        fill={color}
        opacity="0.18"
      />
    </svg>
  );
}

/**
 * Renders one statistic with a semantic icon and an optional destination or click handler.
 */
export default function StatCard({
  title,
  value,
  change,
  helper,
  tone = "yellow",
  negative = false,
  compact = false,
  href,
  iconKey,
  onClick,
  active = false,
}) {
  const styles = toneMap[tone] || toneMap.yellow;
  const Icon = statIcons[iconKey] || GridIcon;

  const card = (
    <article
      className={`card-shell h-32 min-w-0 overflow-hidden rounded-2xl p-3.5 transition-all duration-200 ${
        active
          ? "ring-2 ring-app-yellow border-app-yellow/80 bg-app-card-soft/90 shadow-[0_0_20px_rgba(242,220,46,0.15)]"
          : ""
      }`}
      dir="rtl"
    >
      <div className="flex items-center justify-between gap-3">
        <div className="min-w-0 flex-1 text-right">
          <h3 className="truncate text-sm font-medium text-app-text">{title}</h3>
          {helper && compact && <p className="mt-1 text-xs text-app-muted">{helper}</p>}
        </div>
        <div className={`grid size-11 shrink-0 place-items-center rounded-full ${styles.icon}`}>
          <Icon className="size-5" />
        </div>
      </div>

      {!compact && (
        <div
          className={`mt-1 text-center text-xl font-medium leading-7 ${tone === "yellow" ? "text-app-yellow" : "text-app-text"}`}
        >
          {value}
        </div>
      )}
      {compact && value && (
        <div
          className={`mt-5 text-center text-xl font-medium ${tone === "yellow" ? "text-app-yellow" : "text-app-text"}`}
        >
          {value}
        </div>
      )}

      {!compact && (
        <div className="mt-1 flex items-end justify-between gap-3">
          <div className="text-start text-[10px] leading-none text-white">
            {change && (
              <div
                className={`flex items-center gap-1 text-sm font-medium leading-5 ${negative ? "text-app-red" : "text-app-green-2"}`}
              >
                <TrendUpIcon className={`size-4 ${negative ? "rotate-180" : ""}`} />
                <span>{change}</span>
              </div>
            )}
            {helper && <span className="block">{helper}</span>}
          </div>
          <MiniSpark tone={tone} />
        </div>
      )}
    </article>
  );

  if (onClick && !href) {
    return (
      <button
        type="button"
        onClick={onClick}
        className="group block w-full min-w-0 rounded-2xl text-right transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow/60 cursor-pointer"
        aria-label={`تصفية حسب ${title}`}
      >
        {card}
      </button>
    );
  }

  if (!href) return card;

  return (
    <Link
      href={href}
      className="group block min-w-0 rounded-2xl transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow/60 cursor-pointer"
      aria-label={`فتح صفحة ${title}`}
    >
      {card}
    </Link>
  );
}

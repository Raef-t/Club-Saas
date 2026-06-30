import { GridIcon, TrendUpIcon } from "@/components/icons/Icons";

const toneMap = {
  yellow: {
    icon: "bg-[rgba(252,205,3,0.22)] text-app-yellow",
    spark: "#FCCD03",
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

export default function StatCard({
  title,
  value,
  change,
  helper,
  tone = "yellow",
  negative = false,
  compact = false,
}) {
  const styles = toneMap[tone] || toneMap.yellow;

  return (
    <article className="card-shell h-32 min-w-[186px] overflow-hidden rounded-2xl p-3.5" dir="rtl">
      <div className="flex items-center justify-between gap-3">
        <div className="min-w-0 flex-1 text-right">
          <h3 className="truncate text-sm font-medium text-app-text">
            {title}
          </h3>
          {helper && compact && (
            <p className="mt-1 text-xs text-app-muted">{helper}</p>
          )}
        </div>
        <div
          className={`grid size-11 shrink-0 place-items-center rounded-full ${styles.icon}`}
        >
          <GridIcon className="size-5" />
        </div>
      </div>

      {!compact && (
        <div className="mt-1 text-center text-xl font-medium leading-7 text-app-text">
          {value}
        </div>
      )}
      {compact && value && (
        <div className="mt-5 text-center text-xl font-medium text-app-text">
          {value}
        </div>
      )}

      {!compact && (
        <div
          className="mt-1 flex items-end justify-between gap-3"
          dir="ltr"
        >
          <div className="text-left text-[10px] leading-none text-white">
            {change && (
              <div
                className={`flex items-center gap-1 text-sm font-medium leading-5 ${negative ? "text-app-red" : "text-app-green-2"}`}
              >
                <TrendUpIcon
                  className={`size-4 ${negative ? "rotate-180" : ""}`}
                />
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
}

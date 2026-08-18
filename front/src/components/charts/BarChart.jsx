"use client";

export default function BarChart({ data = [], height = 200 }) {
  if (!data || data.length === 0) {
    return (
      <div className="flex h-48 w-full items-center justify-center text-xs text-app-muted">
        لا توجد بيانات متاحة
      </div>
    );
  }

  const max = Math.max(...data.map((item) => Number(item.value) || 0), 1);

  return (
    <div className="relative w-full px-3 pt-5 pb-2" dir="rtl">
      {/* Background horizontal grid lines */}
      <div className="absolute inset-x-3 top-5 bottom-12 flex flex-col justify-between pointer-events-none opacity-20">
        <div className="w-full border-t border-dashed border-app-text/40" />
        <div className="w-full border-t border-dashed border-app-text/30" />
        <div className="w-full border-t border-dashed border-app-text/30" />
        <div className="w-full border-t border-app-text/40" />
      </div>

      {/* Main Chart Container */}
      <div
        className="relative flex items-end justify-around gap-2 sm:gap-4"
        style={{ height: `${height}px` }}
      >
        {data.map((item, index) => {
          const val = Math.max(0, Number(item?.value) || 0);
          const heightPercent = val > 0 ? Math.max((val / max) * 100, 6) : 3;
          const rawLabel = String(item?.label ?? "");
          const labelParts = rawLabel
            .split(" - ")
            .map((s) => s.trim())
            .filter((part) => part && part !== "undefined" && part !== "null");

          const displayLabel =
            labelParts.length > 0
              ? labelParts.join(" - ")
              : rawLabel && rawLabel !== "undefined" && rawLabel !== "null"
                ? rawLabel
                : "-";

          return (
            <div
              key={`${displayLabel}-${val}-${index}`}
              className="group relative flex flex-1 flex-col items-center h-full justify-end min-w-0 max-w-[85px] sm:max-w-[100px]"
            >
              {/* Floating Tooltip on Hover */}
              <div className="absolute -top-10 opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none z-30 transform group-hover:-translate-y-1">
                <div className="bg-app-cardSoft text-app-text text-[11px] font-semibold py-1 px-2.5 rounded-lg border border-app-border shadow-xl whitespace-nowrap flex items-center gap-1.5">
                  <span className="inline-block size-2 rounded-full bg-app-yellow shadow-[0_0_6px_#F2DC2E]" />
                  <span>{displayLabel}:</span>
                  <span className="text-app-yellow font-bold">{val}</span>
                </div>
              </div>

              {/* Value Badge above bar */}
              <span className="mb-1 text.5 border-0 text-[11px] font-bold text-app-yellow/90 group-hover:text-app-yellow group-hover:scale-110 transition-all">
                {val}
              </span>

              {/* Column Track & Bar Filler */}
              <div className="relative w-full h-[calc(100%-52px)] flex items-end justify-center rounded-t-lg bg-white/[0.03] group-hover:bg-white/[0.06] transition-colors p-0.5 border-b border-app-border/40">
                <div
                  className="w-full max-w-[28px] sm:max-w-[34px] rounded-t-md bg-gradient-to-t from-app-yellow/70 via-app-yellow to-yellow-300 group-hover:shadow-[0_0_14px_rgba(242,220,46,0.5)] group-hover:brightness-110 transition-all duration-500 ease-out"
                  style={{ height: `${heightPercent}%` }}
                />
              </div>

              {/* X-axis Label below */}
              <div className="mt-2 text-center w-full min-h-[34px] flex flex-col items-center justify-start leading-snug px-0.5">
                {labelParts.length > 1 ? (
                  <>
                    <span className="text-[11px] font-semibold text-app-text break-words group-hover:text-app-yellow transition-colors">
                      {labelParts[0]}
                    </span>
                    <span className="text-[9.5px] text-app-muted-light break-words font-normal">
                      {labelParts.slice(1).join(" - ")}
                    </span>
                  </>
                ) : (
                  <span className="text-[10.5px] font-medium text-app-text break-words group-hover:text-app-yellow transition-colors">
                    {displayLabel}
                  </span>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}


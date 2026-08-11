"use client";

import { useEffect, useRef } from "react";

export default function UserRoleTabs({ items = [], value = "all", onChange, isRefreshing }) {
  const tabRefs = useRef(new Map());

  useEffect(() => {
    tabRefs.current.get(value)?.scrollIntoView?.({
      behavior: "smooth",
      block: "nearest",
      inline: "nearest",
    });
  }, [value]);

  function handleTabKeyDown(event, index) {
    let nextIndex = null;

    if (event.key === "ArrowLeft") nextIndex = (index + 1) % items.length;
    if (event.key === "ArrowRight") nextIndex = (index - 1 + items.length) % items.length;
    if (event.key === "Home") nextIndex = 0;
    if (event.key === "End") nextIndex = items.length - 1;

    if (nextIndex === null) return;

    event.preventDefault();
    onChange?.(items[nextIndex].value);
    event.currentTarget.parentElement?.children[nextIndex]?.focus();
  }

  return (
    <section
      className="app-card relative overflow-hidden rounded-2xl"
      aria-label="تصفية حسابات المستخدمين"
    >
      <div
        className="pointer-events-none absolute -start-16 -top-20 size-52 rounded-full bg-app-yellow-soft blur-3xl"
        aria-hidden="true"
      />

      <div className="relative flex flex-col gap-2 border-b border-app-line px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 className="text-sm font-bold text-app-text">فئات الحسابات</h2>
          <p className="mt-0.5 text-xs text-app-muted">انتقل مباشرة إلى الحسابات حسب الدور</p>
        </div>
        <div className="flex items-center gap-2 text-[11px] text-app-muted-light">
          <span
            className={`size-2 rounded-full ${isRefreshing ? "animate-pulse bg-app-yellow" : "bg-app-green"}`}
            aria-hidden="true"
          />
          <span>{isRefreshing ? "جارٍ تحديث البيانات" : "البيانات محدّثة"}</span>
        </div>
      </div>

      <div className="scrollbar-hidden relative overflow-x-auto scroll-smooth px-4 py-3 [overscroll-behavior-inline:contain]">
        <div className="flex min-w-max items-stretch gap-2 md:min-w-full" role="tablist">
          {items.map((item, index) => {
            const isActive = value === item.value;

            return (
              <button
                key={item.value}
                ref={(node) => {
                  if (node) tabRefs.current.set(item.value, node);
                  else tabRefs.current.delete(item.value);
                }}
                id={`user-role-tab-${item.value}`}
                type="button"
                role="tab"
                aria-selected={isActive}
                aria-controls="user-accounts-panel"
                aria-label={item.label}
                tabIndex={isActive ? 0 : -1}
                onClick={() => onChange?.(item.value)}
                onKeyDown={(event) => handleTabKeyDown(event, index)}
                className={`group relative flex min-h-14 min-w-[156px] flex-1 items-center justify-center overflow-hidden rounded-xl border px-5 py-3 text-center transition-[border-color,background-color,color,box-shadow] duration-200 focus-visible:outline-none ${
                  isActive
                    ? "border-app-yellow bg-app-yellow text-app-on-accent shadow-[0_8px_22px_rgba(242,220,46,0.18)]"
                    : "border-app-line/70 bg-app-card-soft/75 text-app-muted hover:border-app-yellow/35 hover:bg-app-card-hover hover:text-app-text"
                }`}
              >
                <span className="whitespace-nowrap text-sm font-bold">{item.label}</span>

                {isActive && (
                  <span
                    className="absolute inset-x-4 bottom-0 h-0.5 rounded-full bg-app-on-accent/35"
                    aria-hidden="true"
                  />
                )}
              </button>
            );
          })}
        </div>
      </div>
    </section>
  );
}

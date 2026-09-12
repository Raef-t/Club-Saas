"use client";

import { useState } from "react";
import Button from "@/components/ui/Button";
import { ChevronDownIcon, FilterIcon, XIcon } from "@/components/icons/Icons";

export default function ReportFiltersAccordion({
  children,
  activeFiltersCount = 0,
  activeFilters = [],
  validationError = "",
  isFetching = false,
  onApply,
  onReset,
  title = "فلاتر التقرير",
  subtitle = "يمكن الجمع بين أكثر من فلتر للوصول إلى النتائج المطلوبة.",
  contentId = "report-filters-content",
}) {
  const [isOpen, setIsOpen] = useState(false);

  function handleSubmit(event) {
    event.preventDefault();
    if (onApply?.() !== false) setIsOpen(false);
  }

  const resolvedActiveFiltersCount = Math.max(activeFiltersCount, activeFilters.length);

  return (
    <section className="card-shell overflow-hidden rounded-2xl" aria-label={title}>
      <button
        type="button"
        className="group flex w-full items-center justify-between gap-4 px-5 py-4 text-right transition hover:bg-app-card-hover sm:px-6"
        onClick={() => setIsOpen((current) => !current)}
        aria-expanded={isOpen}
        aria-controls={contentId}
      >
        <span className="flex min-w-0 items-center gap-3">
          <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-app-yellow-soft text-app-yellow transition group-hover:bg-app-yellow/15">
            <FilterIcon className="size-4.5" />
          </span>
          <span className="min-w-0">
            <span className="block text-base font-medium text-app-text">{title}</span>
            <span className="mt-1 block text-xs text-app-muted">
              {isOpen
                ? subtitle
                : resolvedActiveFiltersCount > 0
                  ? `${resolvedActiveFiltersCount.toLocaleString("ar")} فلاتر نشطة`
                  : "لا توجد فلاتر مخصصة"}
            </span>
          </span>
        </span>
        <span className="flex shrink-0 items-center gap-3">
          {resolvedActiveFiltersCount > 0 && (
            <span className="rounded-full bg-app-yellow-soft px-2.5 py-1 text-xs font-medium text-app-yellow">
              {resolvedActiveFiltersCount.toLocaleString("ar")}
            </span>
          )}
          <ChevronDownIcon
            className={`size-5 text-app-muted-light transition-transform duration-200 ${isOpen ? "rotate-180" : ""}`}
          />
        </span>
      </button>

      {activeFilters.length > 0 && (
        <div className="flex flex-wrap gap-2 border-t border-app-line/70 bg-app-card-soft/40 px-5 py-3 sm:px-6">
          <span className="py-1 text-xs text-app-muted">الفلاتر المختارة:</span>
          {activeFilters.map((filter) => (
            <span
              key={filter.id || filter.label}
              className="inline-flex max-w-full items-center rounded-full border border-app-yellow/20 bg-app-yellow-soft px-2.5 py-1 text-xs text-app-yellow"
            >
              <span className="truncate">{filter.label}</span>
            </span>
          ))}
        </div>
      )}

      {isOpen && (
        <form
          id={contentId}
          onSubmit={handleSubmit}
          className="space-y-5 border-t border-app-line px-5 py-5 sm:px-6"
        >
          {children}

          {validationError && (
            <p
              className="rounded-lg border border-app-red/30 bg-app-red/10 px-4 py-2 text-sm text-app-red"
              role="alert"
            >
              {validationError}
            </p>
          )}

          <div className="grid grid-cols-2 items-center gap-2 border-t border-app-line pt-4 sm:flex sm:flex-wrap">
            <Button
              type="submit"
              icon={<FilterIcon className="size-4" />}
              loading={isFetching}
              loadingLabel="جاري تطبيق الفلاتر"
              className="w-full sm:w-auto"
            >
              تطبيق الفلاتر
            </Button>
            <Button
              type="button"
              tone="outline"
              icon={<XIcon className="size-4" />}
              onClick={onReset}
              disabled={isFetching}
              className="w-full sm:w-auto"
            >
              إعادة تعيين
            </Button>
          </div>
        </form>
      )}
    </section>
  );
}

"use client";

import ReportFilterSelect from "@/components/reports/ReportFilterSelect";
import ReportFiltersAccordion from "@/components/reports/ReportFiltersAccordion";
import { Field } from "@/components/forms/Field";
import { TIME_CAPACITY_DAY_OPTIONS } from "./timeCapacityReportUtils";

export default function TimeCapacityReportFilters({
  filters,
  activities,
  plans,
  validationError,
  isFetching,
  onChange,
  onApply,
  onReset,
}) {
  const activeFiltersCount = [
    filters.startTime,
    filters.endTime,
    filters.dayOfWeek !== "all" && filters.dayOfWeek,
    filters.planId,
    filters.activityId,
  ].filter(Boolean).length;

  return (
    <ReportFiltersAccordion
      activeFiltersCount={activeFiltersCount}
      validationError={validationError}
      isFetching={isFetching}
      onApply={onApply}
      onReset={onReset}
      subtitle="صفِّ النتائج حسب الوقت واليوم والنشاط وخطة الاشتراك."
      contentId="time-capacity-report-filters-content"
    >
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Field
          type="time"
          label="وقت البداية"
          required={false}
          value={filters.startTime}
          onChange={(value) => onChange("startTime", value)}
        />

        <Field
          type="time"
          label="وقت النهاية"
          required={false}
          value={filters.endTime}
          onChange={(value) => onChange("endTime", value)}
        />

        <ReportFilterSelect
          label="يوم الأسبوع"
          value={filters.dayOfWeek}
          options={TIME_CAPACITY_DAY_OPTIONS}
          onChange={(value) => onChange("dayOfWeek", value)}
        />

        <ReportFilterSelect
          label="النشاط الرياضي"
          value={filters.activityId}
          options={[{ value: "", label: "كل الأنشطة" }, ...activities]}
          onChange={(value) => onChange("activityId", value)}
          searchable={activities.length > 7}
        />

        <ReportFilterSelect
          label="خطة الاشتراك"
          value={filters.planId}
          options={[{ value: "", label: "كل الخطط" }, ...plans]}
          onChange={(value) => onChange("planId", value)}
          searchable={plans.length > 7}
        />
      </div>
    </ReportFiltersAccordion>
  );
}

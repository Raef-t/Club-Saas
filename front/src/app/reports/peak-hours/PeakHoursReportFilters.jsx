"use client";

import ReportFilterSelect from "@/components/reports/ReportFilterSelect";
import ReportFiltersAccordion from "@/components/reports/ReportFiltersAccordion";
import { Field } from "@/components/forms/Field";
import { PEAK_HOURS_ATTENDABLE_OPTIONS } from "./peakHoursReportUtils";

export default function PeakHoursReportFilters({
  filters,
  validationError,
  isFetching,
  onChange,
  onApply,
  onReset,
}) {
  const activeFiltersCount = [
    filters.startDate,
    filters.endDate,
    filters.attendableType !== "all" && filters.attendableType,
  ].filter(Boolean).length;

  return (
    <ReportFiltersAccordion
      activeFiltersCount={activeFiltersCount}
      validationError={validationError}
      isFetching={isFetching}
      onApply={onApply}
      onReset={onReset}
      subtitle="حدّد الفترة ونوع الحضور المطلوب تحليله."
      contentId="peak-hours-report-filters-content"
    >
      <div className="grid gap-4 md:grid-cols-3">
        <Field
          type="date"
          label="تاريخ البداية"
          required={false}
          value={filters.startDate}
          onChange={(value) => onChange("startDate", value)}
        />
        <Field
          type="date"
          label="تاريخ النهاية"
          required={false}
          value={filters.endDate}
          onChange={(value) => onChange("endDate", value)}
        />
        <ReportFilterSelect
          label="نوع الحضور"
          value={filters.attendableType}
          options={PEAK_HOURS_ATTENDABLE_OPTIONS}
          onChange={(value) => onChange("attendableType", value)}
        />
      </div>
    </ReportFiltersAccordion>
  );
}

"use client";

import ReportFilterSelect from "@/components/reports/ReportFilterSelect";
import ReportFiltersAccordion from "@/components/reports/ReportFiltersAccordion";
import { Field } from "@/components/forms/Field";
import { SHIFT_ATTENDANCE_MODE_OPTIONS } from "./shiftAttendanceReportUtils";

export default function ShiftAttendanceReportFilters({
  filters,
  activities,
  shifts,
  validationError,
  isFetching,
  onChange,
  onApply,
  onReset,
}) {
  const activeFiltersCount = [
    filters.mode !== "all" &&
      (filters.date || filters.month || filters.startDate || filters.endDate),
    filters.activityId,
    filters.shiftId,
  ].filter(Boolean).length;

  return (
    <ReportFiltersAccordion
      activeFiltersCount={activeFiltersCount}
      validationError={validationError}
      isFetching={isFetching}
      onApply={onApply}
      onReset={onReset}
      subtitle="تصفية الحضور حسب يوم أو شهر أو نطاق زمني، مع تحديد النشاط والوردية."
      contentId="shift-attendance-report-filters-content"
    >
      <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
        <ReportFilterSelect
          label="نمط التصفية الزمنية"
          value={filters.mode}
          options={SHIFT_ATTENDANCE_MODE_OPTIONS}
          onChange={(value) => onChange("mode", value)}
        />
        {filters.mode === "date" && (
          <Field
            type="date"
            label="تاريخ اليوم المحدد"
            required={false}
            value={filters.date}
            onChange={(value) => onChange("date", value)}
          />
        )}
        {filters.mode === "month" && (
          <Field
            type="month"
            label="الشهر المحدد"
            required={false}
            value={filters.month}
            onChange={(event) => onChange("month", event.target.value)}
          />
        )}
        {filters.mode === "range" && (
          <>
            <Field
              type="date"
              label="تاريخ بداية النطاق"
              required={false}
              value={filters.startDate}
              onChange={(value) => onChange("startDate", value)}
            />
            <Field
              type="date"
              label="تاريخ نهاية النطاق"
              required={false}
              value={filters.endDate}
              onChange={(value) => onChange("endDate", value)}
            />
          </>
        )}
        <ReportFilterSelect
          label="النشاط الرياضي"
          value={filters.activityId}
          options={[{ value: "", label: "كل الأنشطة" }, ...activities]}
          onChange={(value) => onChange("activityId", value)}
          searchable={activities.length > 7}
        />
        <ReportFilterSelect
          label="وردية محددة"
          value={filters.shiftId}
          options={[{ value: "", label: "كل الورديات" }, ...shifts]}
          onChange={(value) => onChange("shiftId", value)}
          searchable={shifts.length > 7}
        />
      </div>
    </ReportFiltersAccordion>
  );
}

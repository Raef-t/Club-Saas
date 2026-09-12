"use client";

import ReportFilterSelect from "@/components/reports/ReportFilterSelect";
import ReportFiltersAccordion from "@/components/reports/ReportFiltersAccordion";
import { Field } from "@/components/forms/Field";
import { SearchIcon } from "@/components/icons/Icons";
import {
  RENEWAL_DATE_FILTER_OPTIONS,
  RENEWAL_STATUS_TYPE_OPTIONS,
} from "./renewalStatusReportUtils";

export default function RenewalStatusReportFilters({
  filters,
  plans,
  coaches,
  validationError,
  isFetching,
  onChange,
  onApply,
  onReset,
}) {
  const activeFiltersCount = [
    filters.type !== "all" && filters.type,
    filters.dateFilterBy !== "end_date" && filters.dateFilterBy,
    filters.planId,
    filters.coachId,
    filters.startDate,
    filters.endDate,
    String(filters.search || "").trim(),
  ].filter(Boolean).length;

  return (
    <ReportFiltersAccordion
      activeFiltersCount={activeFiltersCount}
      validationError={validationError}
      isFetching={isFetching}
      onApply={onApply}
      onReset={onReset}
      subtitle="تصفية الاشتراكات بحسب نوع التقرير والمعيار الزمني والمدرب والخطة."
      contentId="renewal-report-filters-content"
    >
      <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
        <ReportFilterSelect
          label="نوع التقرير"
          value={filters.type}
          options={RENEWAL_STATUS_TYPE_OPTIONS}
          onChange={(value) => onChange("type", value)}
        />
        <ReportFilterSelect
          label="المعيار الزمني للتصفية"
          value={filters.dateFilterBy}
          options={RENEWAL_DATE_FILTER_OPTIONS}
          onChange={(value) => onChange("dateFilterBy", value)}
        />
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
        <ReportFilterSelect
          label="خطة الاشتراك"
          value={filters.planId}
          options={[{ value: "", label: "كل الخطط" }, ...plans]}
          onChange={(value) => onChange("planId", value)}
          searchable={plans.length > 7}
        />
        <ReportFilterSelect
          label="المدرب المسند"
          value={filters.coachId}
          options={[{ value: "", label: "كل المدربين" }, ...coaches]}
          onChange={(value) => onChange("coachId", value)}
          searchable={coaches.length > 7}
        />
        <Field
          type="search"
          label="البحث"
          required={false}
          icon={SearchIcon}
          placeholder="اسم اللاعب، رقم الهاتف، أو رقم العضوية"
          value={filters.search}
          onChange={(event) => onChange("search", event.target.value)}
          className="md:col-span-2"
        />
      </div>
    </ReportFiltersAccordion>
  );
}

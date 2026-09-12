"use client";

import ReportFilterSelect from "@/components/reports/ReportFilterSelect";
import ReportFiltersAccordion from "@/components/reports/ReportFiltersAccordion";
import { Field } from "@/components/forms/Field";
import { SearchIcon } from "@/components/icons/Icons";
import {
  SUBSCRIPTION_REPORT_PAYMENT_OPTIONS,
  SUBSCRIPTION_REPORT_STATUS_OPTIONS,
} from "./subscriptionReportUtils";

export default function SubscriptionsReportFilters({
  filters,
  plans,
  coaches,
  validationError,
  isFetching,
  onChange,
  onApply,
  onReset,
}) {
  const optionLabel = (options, value) =>
    options.find((option) => String(option.value) === String(value))?.label || value;
  const activeFilters = [
    filters.status !== "all" && {
      id: "status",
      label: `حالة الاشتراك: ${optionLabel(SUBSCRIPTION_REPORT_STATUS_OPTIONS, filters.status)}`,
    },
    filters.planId && {
      id: "plan",
      label: `الخطة: ${optionLabel(plans, filters.planId)}`,
    },
    filters.paymentStatus !== "all" && {
      id: "payment",
      label: `الدفع: ${optionLabel(SUBSCRIPTION_REPORT_PAYMENT_OPTIONS, filters.paymentStatus)}`,
    },
    filters.coachId && {
      id: "coach",
      label: `الكوتش: ${optionLabel(coaches, filters.coachId)}`,
    },
    filters.startDate && { id: "start-date", label: `من: ${filters.startDate}` },
    filters.endDate && { id: "end-date", label: `إلى: ${filters.endDate}` },
    String(filters.search || "").trim() && {
      id: "search",
      label: `البحث: ${String(filters.search).trim()}`,
    },
  ].filter(Boolean);

  return (
    <ReportFiltersAccordion
      activeFiltersCount={activeFilters.length}
      activeFilters={activeFilters}
      validationError={validationError}
      isFetching={isFetching}
      onApply={onApply}
      onReset={onReset}
      contentId="subscriptions-report-filters-content"
    >
      <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
        <ReportFilterSelect
          label="حالة الاشتراك"
          value={filters.status}
          options={SUBSCRIPTION_REPORT_STATUS_OPTIONS}
          onChange={(value) => onChange("status", value)}
        />

        <ReportFilterSelect
          label="خطة الاشتراك"
          value={filters.planId}
          options={[{ value: "", label: "كل الخطط" }, ...plans]}
          onChange={(value) => onChange("planId", value)}
          searchable={plans.length > 7}
        />

        <ReportFilterSelect
          label="حالة الدفع"
          value={filters.paymentStatus}
          options={SUBSCRIPTION_REPORT_PAYMENT_OPTIONS}
          onChange={(value) => onChange("paymentStatus", value)}
        />

        <ReportFilterSelect
          label="الكوتش المسند"
          value={filters.coachId}
          options={[{ value: "", label: "كل الكوتشات" }, ...coaches]}
          onChange={(value) => onChange("coachId", value)}
          searchable={coaches.length > 7}
        />

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

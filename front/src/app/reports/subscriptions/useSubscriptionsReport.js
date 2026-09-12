"use client";

import { useMemo, useState } from "react";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetSubscriptionsReportQuery } from "@/lib/api/reportsApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createSubscriptionsReportParams,
  getReportLookupCollection,
  normalizeSubscriptionReportRecord,
  normalizeSubscriptionsReportResponse,
  validateSubscriptionsReportFilters,
} from "./subscriptionReportUtils";

export const DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS = {
  status: "all",
  planId: "",
  paymentStatus: "all",
  coachId: "",
  startDate: "",
  endDate: "",
  search: "",
};

export function useSubscriptionsReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const [draftFilters, setDraftFilters] = useState(DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS);
  const [appliedFilters, setAppliedFilters] = useState(DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS);
  const [validationError, setValidationError] = useState("");

  const queryParams = useMemo(
    () => createSubscriptionsReportParams(appliedFilters, selectedBranchId),
    [appliedFilters, selectedBranchId],
  );
  const lookupParams = useMemo(
    () => ({
      ...(selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}),
      per_page: "all",
    }),
    [selectedBranchId],
  );

  const reportQuery = useGetSubscriptionsReportQuery(queryParams);
  const plansQuery = useGetSubscriptionPlansQuery(lookupParams);
  const coachesQuery = useGetCoachesQuery(lookupParams);

  const report = useMemo(
    () => normalizeSubscriptionsReportResponse(reportQuery.currentData),
    [reportQuery.currentData],
  );
  const rows = useMemo(
    () => report.records.map((record, index) => normalizeSubscriptionReportRecord(record, index)),
    [report.records],
  );
  const plans = useMemo(
    () =>
      getReportLookupCollection(plansQuery.currentData).map((plan) => ({
        value: String(plan.id),
        label: formatLocalizedName(plan.name),
      })),
    [plansQuery.currentData],
  );
  const coaches = useMemo(
    () =>
      getReportLookupCollection(coachesQuery.currentData).map((coach) => ({
        value: String(coach.id),
        label: coach?.person?.full_name || coach?.full_name || formatLocalizedName(coach?.name),
      })),
    [coachesQuery.currentData],
  );
  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  function updateFilter(name, value) {
    setDraftFilters((current) => ({ ...current, [name]: value }));
    if (validationError) setValidationError("");
  }

  function applyFilters() {
    const error = validateSubscriptionsReportFilters(draftFilters);
    setValidationError(error);
    if (error) return false;
    setAppliedFilters({ ...draftFilters });
    return true;
  }

  function resetFilters() {
    setDraftFilters(DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS);
    setAppliedFilters(DEFAULT_SUBSCRIPTIONS_REPORT_FILTERS);
    setValidationError("");
  }

  return {
    draftFilters,
    updateFilter,
    applyFilters,
    resetFilters,
    validationError,
    report,
    rows,
    plans,
    coaches,
    branchName,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

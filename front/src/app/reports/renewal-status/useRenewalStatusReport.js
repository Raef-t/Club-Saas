"use client";

import { useMemo, useState } from "react";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetSubscriptionRenewalStatusReportQuery } from "@/lib/api/reportsApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createRenewalReportParams,
  DEFAULT_RENEWAL_REPORT_FILTERS,
  getLookupCollection,
  normalizeRenewalReportResponse,
  validateRenewalReportFilters,
} from "./renewalStatusReportUtils";

export function useRenewalStatusReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const [draftFilters, setDraftFilters] = useState(DEFAULT_RENEWAL_REPORT_FILTERS);
  const [appliedFilters, setAppliedFilters] = useState(DEFAULT_RENEWAL_REPORT_FILTERS);
  const [validationError, setValidationError] = useState("");
  const [selectedRecord, setSelectedRecord] = useState(null);

  const queryParams = useMemo(
    () => createRenewalReportParams(appliedFilters, selectedBranchId),
    [appliedFilters, selectedBranchId],
  );

  const lookupParams = useMemo(
    () => ({
      ...(selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}),
      per_page: "all",
    }),
    [selectedBranchId],
  );

  const reportQuery = useGetSubscriptionRenewalStatusReportQuery(queryParams);
  const plansQuery = useGetSubscriptionPlansQuery(lookupParams);
  const coachesQuery = useGetCoachesQuery(lookupParams);

  const report = useMemo(
    () => normalizeRenewalReportResponse(reportQuery.currentData),
    [reportQuery.currentData],
  );

  const plans = useMemo(
    () =>
      getLookupCollection(plansQuery.currentData).map((plan) => ({
        value: String(plan.id),
        label: formatLocalizedName(plan.name),
      })),
    [plansQuery.currentData],
  );

  const coaches = useMemo(
    () =>
      getLookupCollection(coachesQuery.currentData).map((coach) => ({
        value: String(coach.id),
        label:
          coach?.person?.full_name ||
          coach?.full_name ||
          formatLocalizedName(coach?.name),
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
    const error = validateRenewalReportFilters(draftFilters);
    setValidationError(error);
    if (error) return false;
    setAppliedFilters({ ...draftFilters });
    return true;
  }

  function resetFilters() {
    setDraftFilters(DEFAULT_RENEWAL_REPORT_FILTERS);
    setAppliedFilters(DEFAULT_RENEWAL_REPORT_FILTERS);
    setValidationError("");
  }

  return {
    draftFilters,
    updateFilter,
    applyFilters,
    resetFilters,
    validationError,
    report,
    rows: report.records,
    summary: report.summary,
    plans,
    coaches,
    branchName,
    selectedRecord,
    setSelectedRecord,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

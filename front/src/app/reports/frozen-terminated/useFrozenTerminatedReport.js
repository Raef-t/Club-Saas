"use client";

import { useMemo, useState } from "react";
import { useGetFrozenTerminatedSubscriptionsReportQuery } from "@/lib/api/reportsApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createFrozenTerminatedParams,
  DEFAULT_FROZEN_TERMINATED_FILTERS,
  getLookupCollection,
  normalizeFrozenTerminatedResponse,
  validateFrozenTerminatedFilters,
} from "./frozenTerminatedReportUtils";

export function useFrozenTerminatedReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const [draftFilters, setDraftFilters] = useState(DEFAULT_FROZEN_TERMINATED_FILTERS);
  const [appliedFilters, setAppliedFilters] = useState(DEFAULT_FROZEN_TERMINATED_FILTERS);
  const [validationError, setValidationError] = useState("");
  const [selectedRecord, setSelectedRecord] = useState(null);

  const queryParams = useMemo(
    () => createFrozenTerminatedParams(appliedFilters, selectedBranchId),
    [appliedFilters, selectedBranchId]
  );

  const lookupParams = useMemo(
    () => ({
      ...(selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}),
      per_page: "all",
    }),
    [selectedBranchId]
  );

  const reportQuery = useGetFrozenTerminatedSubscriptionsReportQuery(queryParams);
  const plansQuery = useGetSubscriptionPlansQuery(lookupParams);

  const report = useMemo(
    () => normalizeFrozenTerminatedResponse(reportQuery.currentData),
    [reportQuery.currentData]
  );

  const plans = useMemo(
    () =>
      getLookupCollection(plansQuery.currentData).map((plan) => ({
        value: String(plan.id),
        label: formatLocalizedName(plan.name),
      })),
    [plansQuery.currentData]
  );

  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  function updateFilter(name, value) {
    setDraftFilters((current) => ({ ...current, [name]: value }));
    if (validationError) setValidationError("");
  }

  function applyFilters() {
    const error = validateFrozenTerminatedFilters(draftFilters);
    setValidationError(error);
    if (error) return false;
    setAppliedFilters({ ...draftFilters });
    return true;
  }

  function resetFilters() {
    setDraftFilters(DEFAULT_FROZEN_TERMINATED_FILTERS);
    setAppliedFilters(DEFAULT_FROZEN_TERMINATED_FILTERS);
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
    branchName,
    selectedRecord,
    setSelectedRecord,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

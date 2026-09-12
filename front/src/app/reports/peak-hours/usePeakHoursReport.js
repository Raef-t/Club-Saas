"use client";

import { useMemo, useState } from "react";
import { useGetPeakHoursReportQuery } from "@/lib/api/reportsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createPeakHoursReportParams,
  normalizePeakHoursReportResponse,
  validatePeakHoursFilters,
} from "./peakHoursReportUtils";

export const DEFAULT_PEAK_HOURS_FILTERS = {
  startDate: "",
  endDate: "",
  attendableType: "all",
};

export function usePeakHoursReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const [draftFilters, setDraftFilters] = useState(DEFAULT_PEAK_HOURS_FILTERS);
  const [appliedFilters, setAppliedFilters] = useState(DEFAULT_PEAK_HOURS_FILTERS);
  const [validationError, setValidationError] = useState("");
  const queryParams = useMemo(
    () => createPeakHoursReportParams(appliedFilters, selectedBranchId),
    [appliedFilters, selectedBranchId],
  );
  const reportQuery = useGetPeakHoursReportQuery(queryParams);
  const report = useMemo(
    () => normalizePeakHoursReportResponse(reportQuery.currentData),
    [reportQuery.currentData],
  );
  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  function updateFilter(name, value) {
    setDraftFilters((current) => ({ ...current, [name]: value }));
    if (validationError) setValidationError("");
  }

  function applyFilters() {
    const error = validatePeakHoursFilters(draftFilters);
    setValidationError(error);
    if (error) return false;
    setAppliedFilters({ ...draftFilters });
    return true;
  }

  function resetFilters() {
    setDraftFilters(DEFAULT_PEAK_HOURS_FILTERS);
    setAppliedFilters(DEFAULT_PEAK_HOURS_FILTERS);
    setValidationError("");
  }

  return {
    draftFilters,
    updateFilter,
    applyFilters,
    resetFilters,
    validationError,
    report,
    branchName,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

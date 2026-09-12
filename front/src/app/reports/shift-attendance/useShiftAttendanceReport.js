"use client";

import { useMemo, useState } from "react";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetBranchShiftsQuery } from "@/lib/api/branchesApi";
import { useGetShiftAttendanceReportQuery } from "@/lib/api/reportsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createShiftAttendanceParams,
  DEFAULT_SHIFT_ATTENDANCE_FILTERS,
  getLookupCollection,
  normalizeShiftAttendanceResponse,
  validateShiftAttendanceFilters,
} from "./shiftAttendanceReportUtils";

export function useShiftAttendanceReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const [draftFilters, setDraftFilters] = useState(DEFAULT_SHIFT_ATTENDANCE_FILTERS);
  const [appliedFilters, setAppliedFilters] = useState(DEFAULT_SHIFT_ATTENDANCE_FILTERS);
  const [validationError, setValidationError] = useState("");
  const [selectedRecord, setSelectedRecord] = useState(null);

  const queryParams = useMemo(
    () => createShiftAttendanceParams(appliedFilters, selectedBranchId),
    [appliedFilters, selectedBranchId]
  );

  const lookupParams = useMemo(
    () => ({
      ...(selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}),
      per_page: "all",
    }),
    [selectedBranchId]
  );

  const reportQuery = useGetShiftAttendanceReportQuery(queryParams);
  const activitiesQuery = useGetActivitiesQuery(lookupParams);
  const shiftsQuery = useGetBranchShiftsQuery(selectedBranchId, {
    skip: !selectedBranchId || selectedBranchId === "all",
  });

  const report = useMemo(
    () => normalizeShiftAttendanceResponse(reportQuery.currentData),
    [reportQuery.currentData]
  );

  const activities = useMemo(
    () =>
      getLookupCollection(activitiesQuery.currentData).map((activity) => ({
        value: String(activity.id),
        label: formatLocalizedName(activity.name),
      })),
    [activitiesQuery.currentData]
  );

  const shifts = useMemo(
    () =>
      getLookupCollection(shiftsQuery.currentData).map((shift) => ({
        value: String(shift.id),
        label: shift.name || `وردية #${shift.id}`,
      })),
    [shiftsQuery.currentData]
  );

  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  function updateFilter(name, value) {
    setDraftFilters((current) => ({ ...current, [name]: value }));
    if (validationError) setValidationError("");
  }

  function applyFilters() {
    const error = validateShiftAttendanceFilters(draftFilters);
    setValidationError(error);
    if (error) return false;
    setAppliedFilters({ ...draftFilters });
    return true;
  }

  function resetFilters() {
    setDraftFilters(DEFAULT_SHIFT_ATTENDANCE_FILTERS);
    setAppliedFilters(DEFAULT_SHIFT_ATTENDANCE_FILTERS);
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
    activities,
    shifts,
    branchName,
    selectedRecord,
    setSelectedRecord,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

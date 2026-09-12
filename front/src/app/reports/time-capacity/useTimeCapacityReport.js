"use client";

import { useMemo, useState } from "react";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetTimeCapacityReportQuery } from "@/lib/api/reportsApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createTimeCapacityReportParams,
  flattenTimeCapacityActivities,
  getTimeCapacityLookupCollection,
  normalizeTimeCapacityReportResponse,
  validateTimeCapacityFilters,
} from "./timeCapacityReportUtils";

export const DEFAULT_TIME_CAPACITY_FILTERS = {
  startTime: "",
  endTime: "",
  dayOfWeek: "all",
  planId: "",
  activityId: "",
};

function planUsesActivity(plan, activityId) {
  const activities = Array.isArray(plan?.activities) ? plan.activities : [];
  return activities.some(
    (activity) =>
      String(activity?.activity_id ?? activity?.activity?.id ?? activity?.id) ===
      String(activityId),
  );
}

export function useTimeCapacityReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const [draftFilters, setDraftFilters] = useState(DEFAULT_TIME_CAPACITY_FILTERS);
  const [appliedFilters, setAppliedFilters] = useState(DEFAULT_TIME_CAPACITY_FILTERS);
  const [validationError, setValidationError] = useState("");
  const queryParams = useMemo(
    () => createTimeCapacityReportParams(appliedFilters, selectedBranchId),
    [appliedFilters, selectedBranchId],
  );
  const lookupParams = useMemo(
    () => ({
      ...(selectedBranchId && selectedBranchId !== "all" ? { branch_id: selectedBranchId } : {}),
      per_page: "all",
    }),
    [selectedBranchId],
  );

  const reportQuery = useGetTimeCapacityReportQuery(queryParams);
  const activitiesQuery = useGetActivitiesQuery(lookupParams);
  const plansQuery = useGetSubscriptionPlansQuery(lookupParams);
  const report = useMemo(
    () => normalizeTimeCapacityReportResponse(reportQuery.currentData),
    [reportQuery.currentData],
  );
  const rows = useMemo(() => flattenTimeCapacityActivities(report.activities), [report.activities]);
  const activities = useMemo(
    () =>
      getTimeCapacityLookupCollection(activitiesQuery.currentData).map((activity) => ({
        value: String(activity.id),
        label: formatLocalizedName(activity.name),
      })),
    [activitiesQuery.currentData],
  );
  const plans = useMemo(() => {
    const allPlans = getTimeCapacityLookupCollection(plansQuery.currentData);
    const matchingPlans = draftFilters.activityId
      ? allPlans.filter((plan) => planUsesActivity(plan, draftFilters.activityId))
      : allPlans;
    const visiblePlans =
      draftFilters.activityId && matchingPlans.length === 0 ? allPlans : matchingPlans;

    return visiblePlans.map((plan) => ({
      value: String(plan.id),
      label: formatLocalizedName(plan.name),
    }));
  }, [draftFilters.activityId, plansQuery.currentData]);
  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  function updateFilter(name, value) {
    setDraftFilters((current) => ({ ...current, [name]: value }));
    if (validationError) setValidationError("");
  }

  function applyFilters() {
    const error = validateTimeCapacityFilters(draftFilters);
    setValidationError(error);
    if (error) return false;
    setAppliedFilters({ ...draftFilters });
    return true;
  }

  function resetFilters() {
    setDraftFilters(DEFAULT_TIME_CAPACITY_FILTERS);
    setAppliedFilters(DEFAULT_TIME_CAPACITY_FILTERS);
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
    activities,
    plans,
    branchName,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

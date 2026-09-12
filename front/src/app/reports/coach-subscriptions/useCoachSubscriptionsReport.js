"use client";

import { useMemo } from "react";
import { useGetCoachSubscriptionsReportQuery } from "@/lib/api/reportsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatLocalizedName } from "@/lib/utils";
import {
  createCoachSubscriptionsReportParams,
  normalizeCoachSubscriptionsReportResponse,
} from "./coachSubscriptionsReportUtils";

export function useCoachSubscriptionsReport() {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const queryParams = useMemo(
    () => createCoachSubscriptionsReportParams(selectedBranchId),
    [selectedBranchId],
  );
  const reportQuery = useGetCoachSubscriptionsReportQuery(queryParams);
  const report = useMemo(
    () => normalizeCoachSubscriptionsReportResponse(reportQuery.currentData),
    [reportQuery.currentData],
  );
  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  return {
    report,
    branchName,
    isLoading: reportQuery.isLoading,
    isFetching: reportQuery.isFetching,
    error: reportQuery.error,
    refresh: reportQuery.refetch,
  };
}

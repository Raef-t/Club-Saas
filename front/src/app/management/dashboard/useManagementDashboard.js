"use client";

import { useMemo } from "react";
import { useGetScheduleQuery } from "@/lib/api/scheduleApi";
import { useGetDashboardStatsStreamQuery } from "@/lib/api/dashboardApi";
import {
  useGetCoachSubscriptionsReportQuery,
  useGetShiftAttendanceReportQuery,
} from "@/lib/api/reportsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  createCoachSubscriptionMix,
  createDashboardStats,
  createTodaySchedule,
  createShiftAttendanceChart,
} from "./dashboardUtils";

/**
 * Coordinates the live dashboard queries with the globally selected branch.
 */
export function useManagementDashboard({ initialData = {} } = {}) {
  const { selectedBranchId } = useManagementBranch();
  const queryParams = useMemo(
    () => (selectedBranchId === "all" ? {} : { branch_id: selectedBranchId }),
    [selectedBranchId],
  );
  const scheduleQuery = useGetScheduleQuery(selectedBranchId);
  const sseStatsQuery = useGetDashboardStatsStreamQuery(queryParams);
  const shiftAttendanceQuery = useGetShiftAttendanceReportQuery(queryParams);
  const coachSubscriptionsQuery = useGetCoachSubscriptionsReportQuery(queryParams);

  const scheduleResponse = scheduleQuery.currentData || initialData.schedule;
  const currentActiveSessions = useMemo(() => {
    if (!sseStatsQuery.currentData) return null;
    return sseStatsQuery.currentData.current_active_session_plans || [];
  }, [sseStatsQuery.currentData]);
  const todaySessions = useMemo(
    () =>
      createTodaySchedule(scheduleResponse, selectedBranchId, new Date(), currentActiveSessions),
    [currentActiveSessions, scheduleResponse, selectedBranchId],
  );
  const stats = useMemo(
    () => createDashboardStats({ sseStats: sseStatsQuery.currentData }),
    [sseStatsQuery.currentData],
  );
  const shiftChart = useMemo(
    () => createShiftAttendanceChart(shiftAttendanceQuery.currentData),
    [shiftAttendanceQuery.currentData],
  );
  const coachSubscriptionMix = useMemo(
    () => createCoachSubscriptionMix(coachSubscriptionsQuery.currentData),
    [coachSubscriptionsQuery.currentData],
  );
  const queries = [scheduleQuery, shiftAttendanceQuery, coachSubscriptionsQuery];
  const hasInitialData = Object.values(initialData).some(Boolean);

  /**
   * Retries every dashboard query without discarding successful cached data.
   */
  function refresh() {
    queries.forEach((query) => query.refetch());
  }

  return {
    stats,
    shiftChart,
    coachSubscriptionMix,
    todaySessions,
    currentActiveSessions: currentActiveSessions || [],
    isStatsLoading: !sseStatsQuery.currentData,
    isLoading: !hasInitialData && queries.some((query) => query.isLoading),
    isRefreshing: queries.some((query) => query.isFetching),
    hasError: queries.some((query) => Boolean(query.error)),
    isCoachSubscriptionsLoading:
      coachSubscriptionsQuery.isLoading ||
      (coachSubscriptionsQuery.isFetching && !coachSubscriptionsQuery.currentData),
    hasCoachSubscriptionsError: Boolean(coachSubscriptionsQuery.error),
    refresh,
  };
}

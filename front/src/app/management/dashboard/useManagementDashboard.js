"use client";

import { useMemo } from "react";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetPlayerSubscriptionsQuery } from "@/lib/api/playerSubscriptionsApi";
import { useGetScheduleQuery } from "@/lib/api/scheduleApi";
import { useGetDashboardStatsStreamQuery } from "@/lib/api/dashboardApi";
import { useGetShiftAttendanceReportQuery } from "@/lib/api/reportsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import {
  createDashboardStats,
  createSubscriptionMix,
  createTodaySchedule,
  createShiftAttendanceChart,
  getDashboardCollection,
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
  const membersQuery = useGetMembersQuery(queryParams);
  const coachesQuery = useGetCoachesQuery(queryParams);
  const subscriptionsQuery = useGetPlayerSubscriptionsQuery(queryParams);
  const scheduleQuery = useGetScheduleQuery(selectedBranchId);
  const sseStatsQuery = useGetDashboardStatsStreamQuery(queryParams);
  const shiftAttendanceQuery = useGetShiftAttendanceReportQuery(queryParams);

  const members = useMemo(
    () =>
      filterEntitiesByBranch(
        getDashboardCollection(membersQuery.currentData || initialData.members),
        selectedBranchId,
      ),
    [initialData.members, membersQuery.currentData, selectedBranchId],
  );
  const coaches = useMemo(
    () =>
      filterEntitiesByBranch(
        getDashboardCollection(coachesQuery.currentData || initialData.coaches),
        selectedBranchId,
      ),
    [coachesQuery.currentData, initialData.coaches, selectedBranchId],
  );
  const subscriptions = useMemo(
    () =>
      filterEntitiesByBranch(
        getDashboardCollection(subscriptionsQuery.currentData || initialData.subscriptions),
        selectedBranchId,
      ),
    [initialData.subscriptions, selectedBranchId, subscriptionsQuery.currentData],
  );
  const scheduleResponse = scheduleQuery.currentData || initialData.schedule;
  const todaySessions = useMemo(
    () => createTodaySchedule(scheduleResponse, selectedBranchId),
    [scheduleResponse, selectedBranchId],
  );
  const stats = useMemo(
    () =>
      createDashboardStats({
        members,
        coaches,
        subscriptions,
        todaySessions,
        sseStats: sseStatsQuery.data,
      }),
    [coaches, members, subscriptions, todaySessions, sseStatsQuery.data],
  );
  const shiftChart = useMemo(
    () => createShiftAttendanceChart(shiftAttendanceQuery.currentData),
    [shiftAttendanceQuery.currentData],
  );
  const subscriptionMix = useMemo(() => createSubscriptionMix(subscriptions), [subscriptions]);
  const currentActiveSessions = useMemo(
    () => sseStatsQuery.data?.current_active_session_plans || [],
    [sseStatsQuery.data?.current_active_session_plans],
  );

  const queries = [membersQuery, coachesQuery, subscriptionsQuery, scheduleQuery, shiftAttendanceQuery];
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
    subscriptionMix,
    todaySessions,
    currentActiveSessions,
    isLoading: !hasInitialData && queries.some((query) => query.isLoading),
    isRefreshing: queries.some((query) => query.isFetching),
    hasError: queries.some((query) => Boolean(query.error)),
    refresh,
  };
}

"use client";

import { useMemo } from "react";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetAttendancesQuery } from "@/lib/api/attendanceApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetPlayerSubscriptionsQuery } from "@/lib/api/playerSubscriptionsApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { formatLocalizedName } from "@/lib/utils";
import { createOperationalReports, getReportCollection } from "./reportUtils";

/**
 * Uses the backend-scoped response when available and filters only server fallback data.
 */
function getScopedCollection(currentData, fallbackData, selectedBranchId) {
  const collection = getReportCollection(currentData || fallbackData);

  if (currentData && selectedBranchId !== "all") {
    return collection;
  }

  return filterEntitiesByBranch(collection, selectedBranchId);
}

/**
 * Coordinates report data queries, branch filtering, and memoized calculations.
 */
export function useOperationalReports({ initialData = {} } = {}) {
  const { selectedBranchId, selectedBranch } = useManagementBranch();
  const queryParams = useMemo(
    () => (selectedBranchId === "all" ? {} : { branch_id: selectedBranchId }),
    [selectedBranchId],
  );
  const membersQuery = useGetMembersQuery(queryParams);
  const coachesQuery = useGetCoachesQuery(queryParams);
  const subscriptionsQuery = useGetPlayerSubscriptionsQuery(queryParams);
  const activitiesQuery = useGetActivitiesQuery(queryParams);
  const attendancesQuery = useGetAttendancesQuery(queryParams);

  const members = useMemo(
    () => getScopedCollection(membersQuery.currentData, initialData.members, selectedBranchId),
    [initialData.members, membersQuery.currentData, selectedBranchId],
  );
  const coaches = useMemo(
    () => getScopedCollection(coachesQuery.currentData, initialData.coaches, selectedBranchId),
    [coachesQuery.currentData, initialData.coaches, selectedBranchId],
  );
  const subscriptions = useMemo(
    () =>
      getScopedCollection(
        subscriptionsQuery.currentData,
        initialData.subscriptions,
        selectedBranchId,
      ),
    [initialData.subscriptions, selectedBranchId, subscriptionsQuery.currentData],
  );
  const activities = useMemo(
    () =>
      getScopedCollection(activitiesQuery.currentData, initialData.activities, selectedBranchId),
    [activitiesQuery.currentData, initialData.activities, selectedBranchId],
  );
  const attendances = useMemo(
    () =>
      getScopedCollection(attendancesQuery.currentData, initialData.attendances, selectedBranchId),
    [attendancesQuery.currentData, initialData.attendances, selectedBranchId],
  );
  const reportBundle = useMemo(
    () =>
      createOperationalReports({
        members,
        coaches,
        subscriptions,
        activities,
        attendances,
      }),
    [activities, attendances, coaches, members, subscriptions],
  );
  const queries = [
    membersQuery,
    coachesQuery,
    subscriptionsQuery,
    activitiesQuery,
    attendancesQuery,
  ];
  const hasInitialData = Object.values(initialData).some(Boolean);
  const branchName =
    selectedBranchId === "all" ? "كل الفروع" : formatLocalizedName(selectedBranch?.name);

  /**
   * Retries all report sources while keeping successful cached collections visible.
   */
  function refresh() {
    queries.forEach((query) => query.refetch());
  }

  return {
    ...reportBundle,
    branchName,
    isLoading: !hasInitialData && queries.some((query) => query.isLoading),
    isRefreshing: queries.some((query) => query.isFetching),
    hasError: queries.some((query) => Boolean(query.error)),
    hasAttendanceError: Boolean(attendancesQuery.error),
    refresh,
  };
}

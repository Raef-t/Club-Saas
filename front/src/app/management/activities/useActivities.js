import { useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useDeleteActivityMutation,
  useGetActivitiesQuery,
  useGetActivityQuery,
} from "@/lib/api/activitiesApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import {
  createActivityStats,
  filterActivities,
  getActivityCollection,
  getActivityRecord,
} from "./activityUtils";
import { getPaginationMeta, useServerPagination } from "@/lib/pagination";

/**
 * Coordinates the activity list, search, details, and deletion lifecycle.
 */
export function useActivities({ initialActivities } = {}) {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [selectedActivity, setSelectedActivity] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const paginationFilterKey = [selectedBranchId, search].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);
  const queryParams = useMemo(
    () => ({
      ...(selectedBranchId === "all" ? {} : { branch_id: selectedBranchId }),
      page,
      per_page: perPage,
    }),
    [page, perPage, selectedBranchId],
  );
  const {
    currentData: activitiesResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetActivitiesQuery(queryParams);
  const {
    currentData: detailsResponse,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetActivityQuery(selectedActivity?.id, {
    skip: !selectedActivity,
  });
  const [deleteActivity, { isLoading: isDeleting }] = useDeleteActivityMutation();
  const canUseInitialActivities =
    page === 1 && perPage === 15 && selectedBranchId === "all";
  const listResponse =
    activitiesResponse || (canUseInitialActivities ? initialActivities : null);
  const allActivities = useMemo(() => getActivityCollection(listResponse), [listResponse]);
  const pagination = useMemo(
    () => getPaginationMeta(listResponse, { page, perPage }),
    [listResponse, page, perPage],
  );
  const branchActivities = useMemo(
    () => filterEntitiesByBranch(allActivities, selectedBranchId),
    [allActivities, selectedBranchId],
  );
  const activities = useMemo(
    () => filterActivities(branchActivities, search),
    [branchActivities, search],
  );
  const totalResults = search.trim() ? activities.length : pagination.total;
  const stats = useMemo(() => createActivityStats(branchActivities), [branchActivities]);
  const detailsActivity = getActivityRecord(detailsResponse) || selectedActivity;

  /**
   * Opens the details drawer with immediate data from the current table row.
   */
  function openDetails(activity) {
    setSelectedActivity(activity);
  }

  /**
   * Closes the details drawer.
   */
  function closeDetails() {
    setSelectedActivity(null);
  }

  /**
   * Opens the destructive confirmation for one activity.
   */
  function requestDelete(activity) {
    setDeleteConfirmation("");
    setDeleteTarget(activity);
  }

  /**
   * Deletes the selected activity after confirmation.
   */
  async function confirmDelete() {
    if (!deleteTarget || deleteConfirmation !== "delete") return;

    try {
      await deleteActivity({
        id: deleteTarget.id,
        confirmation: deleteConfirmation,
      }).unwrap();
      toast.success("تم حذف النشاط بنجاح");
      setDeleteTarget(null);
      setDeleteConfirmation("");
    } catch (deleteError) {
      toast.error(getApiErrorMessage(deleteError, "تعذر حذف النشاط. حاول مرة أخرى."));
    }
  }

  return {
    search,
    setSearch,
    activities,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
    stats,
    isLoading: isLoading && allActivities.length === 0,
    isFetching,
    errorMessage: error ? getApiErrorMessage(error, "تعذر تحميل قائمة الأنشطة الرياضية.") : "",
    retry: refetch,
    selectedActivity,
    detailsActivity,
    detailsError,
    isFetchingDetails,
    openDetails,
    closeDetails,
    deleteTarget,
    setDeleteTarget,
    requestDelete,
    confirmDelete,
    isDeleting,
    deleteConfirmation,
    setDeleteConfirmation,
  };
}

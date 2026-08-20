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
  const {
    currentData: activitiesResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetActivitiesQuery(selectedBranchId === "all" ? {} : { branch_id: selectedBranchId });
  const {
    currentData: detailsResponse,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetActivityQuery(selectedActivity?.id, {
    skip: !selectedActivity,
  });
  const [deleteActivity, { isLoading: isDeleting }] = useDeleteActivityMutation();
  const allActivities = useMemo(
    () => getActivityCollection(activitiesResponse || initialActivities),
    [activitiesResponse, initialActivities],
  );
  const branchActivities = useMemo(
    () => filterEntitiesByBranch(allActivities, selectedBranchId),
    [allActivities, selectedBranchId],
  );
  const activities = useMemo(
    () => filterActivities(branchActivities, search),
    [branchActivities, search],
  );
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
      await deleteActivity(deleteTarget.id).unwrap();
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

import { useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useDeleteBranchMutation,
  useGetBranchesQuery,
  useGetBranchQuery,
  useToggleBranchStatusMutation,
} from "@/lib/api/branchesApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import {
  createBranchStats,
  filterBranches,
  getBranchCollection,
  getBranchRecord,
} from "./branchUtils";

/**
 * Coordinates branch list filters, details, status changes, and deletion.
 */
export function useBranches({ initialBranches } = {}) {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [genderFilter, setGenderFilter] = useState("all");
  const [selectedBranch, setSelectedBranch] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const {
    currentData: branchesResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetBranchesQuery();
  const {
    currentData: detailsResponse,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetBranchQuery(selectedBranch?.id, {
    skip: !selectedBranch,
  });
  const [deleteBranch, { isLoading: isDeleting }] = useDeleteBranchMutation();
  const [toggleBranchStatus, { isLoading: isToggling }] = useToggleBranchStatusMutation();
  const allBranches = useMemo(
    () => getBranchCollection(branchesResponse || initialBranches),
    [branchesResponse, initialBranches],
  );
  const branchScope = useMemo(
    () => filterEntitiesByBranch(allBranches, selectedBranchId, (branch) => [branch.id]),
    [allBranches, selectedBranchId],
  );
  const branches = useMemo(
    () =>
      filterBranches(branchScope, {
        search,
        gender: genderFilter,
      }),
    [branchScope, genderFilter, search],
  );
  const stats = useMemo(() => createBranchStats(branchScope), [branchScope]);
  const detailsBranch = getBranchRecord(detailsResponse) || selectedBranch;

  /**
   * Opens the details drawer with immediate data from the current row.
   */
  function openDetails(branch) {
    setSelectedBranch(branch);
  }

  /**
   * Closes the branch details drawer.
   */
  function closeDetails() {
    setSelectedBranch(null);
  }

  /**
   * Opens the destructive confirmation for one branch.
   */
  function requestDelete(branch) {
    setDeleteConfirmation("");
    setDeleteTarget(branch);
  }

  /**
   * Deletes the selected branch after confirmation.
   */
  async function confirmDelete() {
    if (!deleteTarget || deleteConfirmation !== "delete") return;

    try {
      await deleteBranch({
        id: deleteTarget.id,
        confirmation: deleteConfirmation,
      }).unwrap();
      toast.success("تم حذف الفرع بنجاح");
      setDeleteTarget(null);
      setDeleteConfirmation("");
    } catch (deleteError) {
      toast.error(getApiErrorMessage(deleteError, "تعذر حذف الفرع. حاول مرة أخرى."));
    }
  }

  /**
   * Toggles a branch status and reports the backend result.
   */
  async function toggleStatus(branch) {
    try {
      await toggleBranchStatus(branch.id).unwrap();
      toast.success("تم تغيير حالة الفرع بنجاح");
    } catch (toggleError) {
      toast.error(getApiErrorMessage(toggleError, "تعذر تغيير حالة الفرع. حاول مرة أخرى."));
    }
  }

  return {
    search,
    setSearch,
    genderFilter,
    setGenderFilter,
    branches,
    stats,
    isLoading: isLoading && allBranches.length === 0,
    isFetching,
    errorMessage: error ? getApiErrorMessage(error, "تعذر تحميل الفروع.") : "",
    retry: refetch,
    selectedBranch,
    detailsBranch,
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
    toggleStatus,
    isToggling,
  };
}

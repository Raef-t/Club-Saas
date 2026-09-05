import { useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { useDeleteClubMutation, useGetClubQuery, useGetClubsQuery } from "@/lib/api/clubsApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { createClubStats, filterClubs, getClubCollection, getClubRecord } from "./clubUtils";
import { getPaginationMeta, useServerPagination } from "@/lib/pagination";

/**
 * Coordinates the club list, search, details, and deletion lifecycle.
 */
export function useClubs({ initialClubs } = {}) {
  const toast = useToast();
  const { selectedClubId } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [selectedClub, setSelectedClub] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const paginationFilterKey = [selectedClubId || "all", search].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);
  const needsAllClubs = Boolean(selectedClubId) || Boolean(search.trim());
  const { currentData: clubsResponse, error, isLoading, isFetching, refetch } = useGetClubsQuery(
    needsAllClubs ? { per_page: "all" } : { page, per_page: perPage },
  );
  const {
    currentData: detailsResponse,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetClubQuery(selectedClub?.id, {
    skip: !selectedClub,
  });
  const [deleteClub, { isLoading: isDeleting }] = useDeleteClubMutation();
  const canUseInitialClubs = !needsAllClubs && page === 1 && perPage === 15;
  const listResponse = clubsResponse || (canUseInitialClubs ? initialClubs : null);
  const allClubs = useMemo(() => getClubCollection(listResponse), [listResponse]);
  const pagination = useMemo(
    () => getPaginationMeta(listResponse, { page, perPage }),
    [listResponse, page, perPage],
  );
  const branchClubs = useMemo(
    () =>
      selectedClubId ? allClubs.filter((club) => String(club.id) === selectedClubId) : allClubs,
    [allClubs, selectedClubId],
  );
  const clubs = useMemo(() => filterClubs(branchClubs, search), [branchClubs, search]);
  const totalResults = needsAllClubs ? clubs.length : pagination.total;
  const stats = useMemo(() => createClubStats(branchClubs), [branchClubs]);
  const detailsClub = getClubRecord(detailsResponse) || selectedClub;

  /**
   * Opens the details drawer with immediate data from the current row.
   */
  function openDetails(club) {
    setSelectedClub(club);
  }

  /**
   * Closes the club details drawer.
   */
  function closeDetails() {
    setSelectedClub(null);
  }

  /**
   * Opens the destructive confirmation for one club.
   */
  function requestDelete(club) {
    setDeleteConfirmation("");
    setDeleteTarget(club);
  }

  /**
   * Deletes the selected club after confirmation.
   */
  async function confirmDelete() {
    if (!deleteTarget || deleteConfirmation !== "delete") return;

    try {
      await deleteClub(deleteTarget.id).unwrap();
      toast.success("تم حذف النادي بنجاح");
      setDeleteTarget(null);
      setDeleteConfirmation("");
    } catch (deleteError) {
      toast.error(getApiErrorMessage(deleteError, "تعذر حذف النادي. حاول مرة أخرى."));
    }
  }

  return {
    search,
    setSearch,
    clubs,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
    stats,
    isLoading: isLoading && allClubs.length === 0,
    isFetching,
    errorMessage: error ? getApiErrorMessage(error, "تعذر تحميل بيانات النوادي.") : "",
    retry: refetch,
    selectedClub,
    detailsClub,
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

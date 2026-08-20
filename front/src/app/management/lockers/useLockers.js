import { useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useToast } from "@/components/ui/Toast";
import {
  useDeleteLockerMutation,
  useGetLockersQuery,
  useReleaseLockerReservationMutation,
  useReserveLockerMutation,
} from "@/lib/api/lockersApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  createLockerQueryParams,
  filterLockers,
  getLockerCollection,
  getLockerSummary,
  isLockerEarlyRelease,
} from "./lockerUtils";

/**
 * Coordinates locker list filters and reservation lifecycle actions.
 */
export function useLockers({ initialLockers } = {}) {
  const toast = useToast();
  const searchParams = useSearchParams();
  const urlStatus = searchParams?.get("status") || searchParams?.get("reservation_type");
  const initialStatus =
    urlStatus === "free" || urlStatus === "assign" || urlStatus === "assigned_free"
      ? "assigned_free"
      : urlStatus || "all";

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState(initialStatus);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const [reserveTarget, setReserveTarget] = useState(null);
  const [releaseTarget, setReleaseTarget] = useState(null);
  const [reserveError, setReserveError] = useState("");

  const queryParams = useMemo(
    () => createLockerQueryParams(branchFilter, statusFilter),
    [branchFilter, statusFilter],
  );
  const {
    currentData: lockersResponse,
    error: lockersError,
    isLoading,
    isFetching,
    refetch,
  } = useGetLockersQuery(queryParams);
  const [deleteLocker, { isLoading: isDeleting }] = useDeleteLockerMutation();
  const [reserveLocker, { isLoading: isReserving }] = useReserveLockerMutation();
  const [releaseReservation, { isLoading: isReleasing }] = useReleaseLockerReservationMutation();

  const allLockers = useMemo(
    () => getLockerCollection(lockersResponse || initialLockers),
    [initialLockers, lockersResponse],
  );
  const lockerSummary = useMemo(
    () => getLockerSummary(lockersResponse || initialLockers),
    [initialLockers, lockersResponse],
  );
  const lockers = useMemo(
    () =>
      filterLockers(allLockers, {
        search,
        branch: branchFilter,
        status: statusFilter,
      }),
    [allLockers, branchFilter, search, statusFilter],
  );

  /**
   * Opens the reservation drawer for the selected locker.
   */
  function openReserve(locker) {
    setReserveError("");
    setReserveTarget(locker);
  }

  /**
   * Closes the reservation drawer and clears its backend error.
   */
  function closeReserve() {
    setReserveTarget(null);
    setReserveError("");
  }

  /**
   * Creates a reservation for the selected locker.
   */
  async function handleReserve(values) {
    if (!reserveTarget) return false;
    setReserveError("");

    try {
      await reserveLocker({ id: reserveTarget.id, ...values }).unwrap();
      toast.success("تم حجز الخزانة بنجاح!");
      closeReserve();
      return true;
    } catch (error) {
      setReserveError(getApiErrorMessage(error, "تعذر حجز الخزانة."));
      return false;
    }
  }

  /**
   * Releases the active reservation after confirmation.
   */
  async function confirmRelease(reason) {
    if (!releaseTarget) return;

    try {
      const body = isLockerEarlyRelease(releaseTarget)
        ? { reason: String(reason || "").trim() }
        : undefined;
      await releaseReservation({ id: releaseTarget.id, body }).unwrap();
      toast.success("تم فك حجز الخزانة بنجاح!");
      setReleaseTarget(null);
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر فك حجز الخزانة."));
    }
  }

  /**
   * Permanently deletes the selected locker after confirmation.
   */
  async function confirmDelete() {
    if (!deleteTarget || deleteConfirmation !== "delete") return;

    try {
      await deleteLocker(deleteTarget.id).unwrap();
      toast.success("تم حذف الخزانة بنجاح!");
      setDeleteTarget(null);
      setDeleteConfirmation("");
    } catch (error) {
      toast.error(
        getApiErrorMessage(error, "تعذر حذف الخزانة. تأكد من عدم ارتباطها ببيانات أخرى."),
      );
    }
  }

  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    statusFilter,
    setStatusFilter,
    lockers,
    lockerSummary,
    lockersErrorMessage: lockersError
      ? getApiErrorMessage(lockersError, "تعذر تحميل الخزائن.")
      : "",
    isLoading,
    isFetching,
    refetch,
    deleteTarget,
    setDeleteTarget,
    confirmDelete,
    isDeleting,
    deleteConfirmation,
    setDeleteConfirmation,
    reserveTarget,
    reserveError,
    openReserve,
    closeReserve,
    handleReserve,
    isReserving,
    releaseTarget,
    setReleaseTarget,
    confirmRelease,
    isReleasing,
    actionsDisabled: isDeleting || isReserving || isReleasing,
  };
}

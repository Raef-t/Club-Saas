import { useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useGetLockersQuery,
  useCreateLockerMutation,
  useDeleteLockerMutation,
  useToggleLockerStatusMutation,
  useUpdateLockerMutation,
  useReserveLockerMutation,
  useReleaseLockerReservationMutation,
} from "@/lib/api/lockersApi";

export function useLockers() {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [branchFilter, setBranchFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [formError, setFormError] = useState("");
  
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const queryParams = {
    branch_id: branchFilter !== "all" ? branchFilter : undefined,
    status: statusFilter !== "all" ? statusFilter : undefined,
  };

  const { data: response, isLoading, isFetching, refetch } = useGetLockersQuery(queryParams);
  const allLockers = response?.data || [];

  const lockers = allLockers.filter((locker) => {
    const matchesSearch = search
      ? locker.locker_number.toLowerCase().includes(search.toLowerCase())
      : true;
    const matchesBranch =
      branchFilter !== "all" ? String(locker.branch_id) === String(branchFilter) : true;
    
    // Status mapping for occupied
    let isOccupied = locker.status === "assigned" || locker.status === "with_member" || locker.status === "with_staff";
    let matchesStatus = true;
    if (statusFilter === "available") {
      matchesStatus = locker.status === "available";
    } else if (statusFilter === "occupied") {
      matchesStatus = isOccupied;
    }
    
    return matchesSearch && matchesBranch && matchesStatus;
  });

  const [createLocker, { isLoading: isCreating }] = useCreateLockerMutation();
  const [deleteLocker, { isLoading: isDeleting }] = useDeleteLockerMutation();
  const [toggleStatus, { isLoading: isToggling }] = useToggleLockerStatusMutation();

  const [updateLocker, { isLoading: isUpdating }] = useUpdateLockerMutation();
  const [reserveLocker, { isLoading: isReserving }] = useReserveLockerMutation();
  const [releaseReservation, { isLoading: isReleasing }] = useReleaseLockerReservationMutation();

  function closeDrawer() {
    setDrawerOpen(false);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      await createLocker(values).unwrap();
      toast.success("تمت إضافة الخزانة بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر إضافة الخزانة. تحقق من البيانات وحاول مرة أخرى."
      );
      return false;
    }
  }

  // Update State
  const [updateModalOpen, setUpdateModalOpen] = useState(false);
  const [itemToUpdate, setItemToUpdate] = useState(null);

  function handleUpdateClick(locker) {
    setItemToUpdate(locker);
    setUpdateModalOpen(true);
  }

  function closeUpdateModal() {
    setUpdateModalOpen(false);
    setItemToUpdate(null);
  }

  async function handleUpdate(values) {
    try {
      await updateLocker({ id: itemToUpdate.id, ...values }).unwrap();
      toast.success("تم تعديل الخزانة بنجاح!");
      closeUpdateModal();
      return true;
    } catch (error) {
      toast.error(error?.data?.message || "تعذر تعديل الخزانة.");
      return false;
    }
  }

  // Reserve State
  const [reserveModalOpen, setReserveModalOpen] = useState(false);
  const [itemToReserve, setItemToReserve] = useState(null);

  function handleReserveClick(locker) {
    setItemToReserve(locker);
    setReserveModalOpen(true);
  }

  function closeReserveModal() {
    setReserveModalOpen(false);
    setItemToReserve(null);
  }

  async function handleReserve(values) {
    try {
      await reserveLocker({ id: itemToReserve.id, ...values }).unwrap();
      toast.success("تم حجز الخزانة بنجاح!");
      closeReserveModal();
      return true;
    } catch (error) {
      toast.error(error?.data?.message || "تعذر حجز الخزانة.");
      return false;
    }
  }

  // Release State
  const [releaseConfirmOpen, setReleaseConfirmOpen] = useState(false);
  const [itemToRelease, setItemToRelease] = useState(null);

  function handleReleaseClick(locker) {
    setItemToRelease(locker);
    setReleaseConfirmOpen(true);
  }

  function closeReleaseConfirm() {
    setReleaseConfirmOpen(false);
    setItemToRelease(null);
  }

  async function confirmRelease() {
    if (!itemToRelease) return;
    try {
      await releaseReservation(itemToRelease.id).unwrap();
      toast.success("تم فك حجز الخزانة بنجاح!");
      closeReleaseConfirm();
    } catch (error) {
      toast.error(error?.data?.message || "تعذر فك حجز الخزانة.");
      closeReleaseConfirm();
    }
  }

  function handleDeleteClick(locker) {
    setItemToDelete(locker);
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deleteLocker(itemToDelete.id).unwrap();
      toast.success("تم حذف الخزانة بنجاح!");
      closeDeleteConfirm();
    } catch (error) {
      toast.error(error?.data?.message || "تعذر حذف الخزانة. تأكد من عدم ارتباطها ببيانات أخرى.");
      closeDeleteConfirm();
    }
  }


  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    statusFilter,
    setStatusFilter,
    drawerOpen,
    setDrawerOpen,
    formError,
    setFormError,
    lockers,
    isLoading,
    isFetching,
    refetch,
    handleCreate,
    isCreating,
    deleteConfirmOpen,
    itemToDelete,
    handleDeleteClick,
    closeDeleteConfirm,
    confirmDelete,
    isDeleting,
    
    // Update
    updateModalOpen,
    itemToUpdate,
    handleUpdateClick,
    closeUpdateModal,
    handleUpdate,
    isUpdating,

    // Reserve
    reserveModalOpen,
    itemToReserve,
    handleReserveClick,
    closeReserveModal,
    handleReserve,
    isReserving,

    // Release
    releaseConfirmOpen,
    itemToRelease,
    handleReleaseClick,
    closeReleaseConfirm,
    confirmRelease,
    isReleasing,
  };
}

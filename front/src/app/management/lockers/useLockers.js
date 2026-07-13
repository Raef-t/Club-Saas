import { useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useGetLockersQuery,
  useCreateLockerMutation,
  useDeleteLockerMutation,
  useToggleLockerStatusMutation,
} from "@/lib/api/lockersApi";

export function useLockers() {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [branchFilter, setBranchFilter] = useState("all");
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [formError, setFormError] = useState("");
  
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const queryParams = {
    search: search || undefined,
    branch_id: branchFilter !== "all" ? branchFilter : undefined,
  };

  const { data: response, isLoading, isFetching, refetch } = useGetLockersQuery();
  const allLockers = response?.data || [];

  const lockers = allLockers.filter((locker) => {
    const matchesSearch = search
      ? locker.locker_number.toLowerCase().includes(search.toLowerCase())
      : true;
    const matchesBranch =
      branchFilter !== "all" ? String(locker.branch_id) === String(branchFilter) : true;
    return matchesSearch && matchesBranch;
  });

  const [createLocker, { isLoading: isCreating }] = useCreateLockerMutation();
  const [deleteLocker, { isLoading: isDeleting }] = useDeleteLockerMutation();
  const [toggleStatus, { isLoading: isToggling }] = useToggleLockerStatusMutation();

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
  };
}

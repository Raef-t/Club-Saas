import { useMemo, useState } from "react";
import {
  useGetClubsQuery,
  useGetClubQuery,
  useCreateClubMutation,
  useUpdateClubMutation,
  useDeleteClubMutation,
} from "@/lib/api/clubsApi";

export function useClubs() {
  const [search, setSearch] = useState("");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedClubId, setSelectedClubId] = useState(null);
  const [formError, setFormError] = useState("");

  const { data, error, isLoading, refetch } = useGetClubsQuery();
  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetClubQuery(selectedClubId, {
    skip: !selectedClubId || drawerMode !== "details",
  });

  const [createClub, { isLoading: isCreating }] = useCreateClubMutation();
  const [updateClub, { isLoading: isUpdating }] = useUpdateClubMutation();
  const [deleteClub, { isLoading: isDeleting }] = useDeleteClubMutation();

  const clubs = useMemo(() => {
    return Array.isArray(responseToClubs(data)) ? responseToClubs(data) : [];
  }, [data]);

  const selectedClub = useMemo(
    () => clubs.find((club) => club.id === selectedClubId) || null,
    [clubs, selectedClubId],
  );

  const detailsClub = useMemo(() => {
    return detailsData?.data || null;
  }, [detailsData]);

  const filteredClubs = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();
    if (!normalizedSearch) return clubs;

    return clubs.filter((club) =>
      [club.name, club.is_active ? "نشط" : "غير نشط"]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(normalizedSearch)),
    );
  }, [clubs, search]);

  const stats = useMemo(() => {
    const activeCount = clubs.filter((club) => club.is_active).length;
    const inactiveCount = clubs.length - activeCount;

    return [
      {
        title: "إجمالي النوادي",
        value: clubs.length.toLocaleString("ar"),
        helper: "كل النوادي المسجلة",
        tone: "yellow",
        compact: true,
      },
      {
        title: "النوادي النشطة",
        value: activeCount.toLocaleString("ar"),
        helper: "النوادي المفتوحة والفعالة",
        tone: "green",
        compact: true,
      },
      {
        title: "النوادي غير النشطة",
        value: inactiveCount.toLocaleString("ar"),
        helper: "النوادي المغلقة مؤقتاً",
        tone: "red",
        compact: true,
      },
    ];
  }, [clubs]);

  function responseToClubs(response) {
    return Array.isArray(response?.data) ? response.data : [];
  }

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedClubId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");

    try {
      await createClub(values).unwrap();
      closeDrawer();
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر إنشاء النادي. تحقق من البيانات وحاول مرة أخرى.",
      );
    }
  }

  async function handleUpdate(values) {
    if (!selectedClubId) return;
    setFormError("");

    try {
      await updateClub({ id: selectedClubId, body: values }).unwrap();
      closeDrawer();
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر تعديل النادي. تحقق من البيانات وحاول مرة أخرى.",
      );
    }
  }

  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  function handleDelete(club) {
    setItemToDelete(club);
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deleteClub(itemToDelete.id).unwrap();
    } catch {
      window.alert("تعذر حذف النادي. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  function getEditInitialValues() {
    return {
      name: selectedClub?.name || "",
      logo_url: selectedClub?.logo_url || "",
      is_active: selectedClub ? Boolean(selectedClub.is_active) : true,
    };
  }

  return {
    search,
    setSearch,
    drawerMode,
    setDrawerMode,
    selectedClubId,
    setSelectedClubId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredClubs,
    stats,
    selectedClub,
    detailsClub,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    closeDrawer,
    getEditInitialValues,
    deleteConfirmOpen,
    itemToDelete,
    closeDeleteConfirm,
    confirmDelete,
  };
}

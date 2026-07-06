import { useMemo, useState } from "react";
import {
  useGetActivitiesQuery,
  useGetActivityQuery,
  useCreateActivityMutation,
  useUpdateActivityMutation,
  useDeleteActivityMutation,
} from "@/lib/api/activitiesApi";
import { useToast } from "@/components/ui/Toast";

function getActivitiesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function activityName(act) {
  if (!act?.name) return "-";
  if (typeof act.name === "string") return act.name;
  return act.name.ar || act.name.en || "-";
}

export function useActivities({ selectedActivityId: initialSelectedActivityId = null } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedActivityId, setSelectedActivityId] = useState(initialSelectedActivityId);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const { data, error, isLoading, refetch } = useGetActivitiesQuery();

  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetActivityQuery(selectedActivityId, {
    skip: !selectedActivityId || drawerMode !== "details",
  });

  const [createActivity, { isLoading: isCreating }] =
    useCreateActivityMutation();
  const [updateActivity, { isLoading: isUpdating }] =
    useUpdateActivityMutation();
  const [deleteActivity, { isLoading: isDeleting }] =
    useDeleteActivityMutation();

  const activities = useMemo(() => getActivitiesArray(data), [data]);

  const selectedActivity = useMemo(
    () => activities.find((a) => a.id === selectedActivityId) || null,
    [activities, selectedActivityId],
  );

  const detailsActivity = useMemo(() => detailsData?.data || null, [detailsData]);

  const filteredActivities = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return activities.filter((act) => {
      const nameVal =
        typeof act.name === "string"
          ? act.name
          : act.name?.ar || act.name?.en || "";
      const descVal = act.description || "";

      const matchesSearch =
        !normalizedSearch ||
        [nameVal, descVal]
          .filter(Boolean)
          .some((value) =>
            String(value).toLowerCase().includes(normalizedSearch),
          );

      return matchesSearch;
    });
  }, [activities, search]);

  const stats = useMemo(() => {
    const activeCount = activities.filter((a) => a.is_active !== false).length;
    const privateCount = activities.filter((a) => a.is_private_equipment === 1 || a.is_private_equipment === true).length;
    const groupCount = activities.filter((a) => a.is_private_equipment === 0 || a.is_private_equipment === false).length;

    return [
      {
        title: "إجمالي الأنشطة",
        value: activities.length.toLocaleString("ar"),
        helper: "الأنشطة الرياضية المسجلة",
        tone: "yellow",
        compact: true,
      },
      {
        title: "أنشطة نشطة",
        value: activeCount.toLocaleString("ar"),
        helper: "المتاحة للحجز حالياً",
        tone: "green",
        compact: true,
      },
      {
        title: "أنشطة جماعية",
        value: groupCount.toLocaleString("ar"),
        helper: "تمارين جماعية عامة",
        tone: "blue",
        compact: true,
      },
      {
        title: "تدريب خاص / أجهزة خاصة",
        value: privateCount.toLocaleString("ar"),
        helper: "أجهزة وتدريبات خاصة",
        tone: "purple",
        compact: true,
      },
    ];
  }, [activities]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedActivityId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      await createActivity(values).unwrap();
      toast.success("تم إنشاء النشاط بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر إنشاء النشاط. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedActivityId) return false;
    setFormError("");
    try {
      await updateActivity({ id: selectedActivityId, body: values }).unwrap();
      toast.success("تم تعديل النشاط بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر تعديل النشاط. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  function handleDelete(act) {
    setItemToDelete(act);
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deleteActivity(itemToDelete.id).unwrap();
      toast.success("تم حذف النشاط بنجاح!");
    } catch {
      toast.error("تعذر حذف النشاط. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  function getEditInitialValues() {
    if (!selectedActivity) return null;

    const nameAr =
      typeof selectedActivity.name === "object"
        ? selectedActivity.name?.ar
        : selectedActivity.name;
    const nameEn =
      typeof selectedActivity.name === "object" ? selectedActivity.name?.en : "";

    return {
      name_ar: nameAr || "",
      name_en: nameEn || "",
      description: selectedActivity.description || "",
      type: selectedActivity.type || "group_class",
      default_capacity: String(selectedActivity.default_capacity || 20),
      is_private_equipment: selectedActivity.is_private_equipment ? "1" : "0",
      gender_allowed: selectedActivity.gender_allowed || "mixed",
    };
  }

  return {
    search,
    setSearch,
    drawerMode,
    setDrawerMode,
    selectedActivityId,
    setSelectedActivityId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredActivities,
    stats,
    selectedActivity,
    detailsActivity,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    getEditInitialValues,
    closeDrawer,
  };
}

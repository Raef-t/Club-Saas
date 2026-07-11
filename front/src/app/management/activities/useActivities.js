import { useMemo, useState } from "react";
import {
  useGetActivitiesQuery,
  useGetActivityQuery,
  useCreateActivityMutation,
  useUpdateActivityMutation,
  useDeleteActivityMutation,
  useGetActivityTypesQuery,
} from "@/lib/api/activitiesApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";

function getActivitiesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function activityName(act) {
  if (!act?.name) return "-";
  if (typeof act.name === "string") return act.name;
  return act.name.ar || act.name.en || "-";
}

export function useActivities(params = {}) {
  const { selectedActivityId: initialSelectedId } = params;
  const [search, setSearch] = useState("");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedActivityId, setSelectedActivityId] = useState(initialSelectedId || null);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const { data, error, isLoading, refetch } = useGetActivitiesQuery();
  const { data: branchesData } = useGetBranchesQuery();
  const { data: activityTypesData } = useGetActivityTypesQuery();

  const branches = useMemo(() => {
    return Array.isArray(branchesData?.data) ? branchesData.data : [];
  }, [branchesData]);

  const activityTypes = useMemo(() => {
    return Array.isArray(activityTypesData?.data) ? activityTypesData.data : [];
  }, [activityTypesData]);

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
    const inactiveCount = activities.length - activeCount;

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
        title: "أنشطة غير نشطة",
        value: inactiveCount.toLocaleString("ar"),
        helper: "الأنشطة الموقوفة مؤقتاً",
        tone: "default",
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
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Create activity validation/API error:", submitError);
      if (submitError?.data?.errors) {
        console.error("Detailed validation errors:", submitError.data.errors);
      }
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
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Update activity validation/API error:", submitError);
      if (submitError?.data?.errors) {
        console.error("Detailed validation errors:", submitError.data.errors);
      }
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
    } catch {
      window.alert("تعذر حذف النشاط. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  function getEditInitialValues() {
    if (!selectedActivity) return null;

    const name =
      typeof selectedActivity.name === "object"
        ? selectedActivity.name?.ar || selectedActivity.name?.en || ""
        : selectedActivity.name || "";

    const shiftIds = Array.isArray(selectedActivity.shifts)
      ? selectedActivity.shifts.map((s) => (typeof s === "object" ? s.id : s))
      : [];

    return {
      name: name,
      description: selectedActivity.description || "",
      gender_allowed: selectedActivity.gender_allowed || "mixed",
      branch_id: selectedActivity.branch_id ? String(selectedActivity.branch_id) : "",
      activity_type_id: selectedActivity.activity_type_id
        ? String(selectedActivity.activity_type_id)
        : selectedActivity.activity_type?.id
        ? String(selectedActivity.activity_type.id)
        : "",
      is_active: selectedActivity.is_active !== false,
      shifts: shiftIds,
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
    branches,
    activityTypes,
  };
}

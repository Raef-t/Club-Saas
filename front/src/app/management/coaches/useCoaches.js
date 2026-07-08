import { useEffect, useMemo, useState } from "react";
import {
  useGetCoachesQuery,
  useGetCoachQuery,
  useCreateCoachMutation,
  useUpdateCoachBasicMutation,
  useUpdateCoachDetailsMutation,
  useDeleteCoachMutation,
  useAddCoachActivitiesMutation,
  useDeleteCoachActivityMutation,
} from "@/lib/api/coachesApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useToast } from "@/components/ui/Toast";

function getCoachesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getBranchesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getActivitiesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

export function useCoaches({ selectedCoachId: initialSelectedCoachId = null } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [branchFilter, setBranchFilter] = useState("all");
  const [employmentFilter, setEmploymentFilter] = useState("all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedCoachId, setSelectedCoachId] = useState(initialSelectedCoachId);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [selectedActivityId, setSelectedActivityId] = useState("");

  const { data, error, isLoading, refetch } = useGetCoachesQuery();
  const { data: branchesData } = useGetBranchesQuery();
  const { data: activitiesData } = useGetActivitiesQuery();

  useEffect(() => {
    if (error) {
      console.error("Coaches list query error:", error);
    }
  }, [error]);

  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetCoachQuery(selectedCoachId, {
    skip: !selectedCoachId || drawerMode !== "details",
  });

  useEffect(() => {
    if (detailsError) {
      console.error("Coach details query error:", detailsError);
    }
  }, [detailsError]);

  const [createCoach, { isLoading: isCreating }] = useCreateCoachMutation();
  const [updateCoachBasic, { isLoading: isUpdatingBasic }] =
    useUpdateCoachBasicMutation();
  const [updateCoachDetails, { isLoading: isUpdatingDetails }] =
    useUpdateCoachDetailsMutation();
  const [deleteCoach, { isLoading: isDeleting }] = useDeleteCoachMutation();
  const [addCoachActivity, { isLoading: isAddingActivity }] =
    useAddCoachActivitiesMutation();
  const [deleteCoachActivity, { isLoading: isDeletingActivity }] =
    useDeleteCoachActivityMutation();

  const coaches = useMemo(() => getCoachesArray(data), [data]);
  const branches = useMemo(() => getBranchesArray(branchesData), [branchesData]);
  const activities = useMemo(
    () => getActivitiesArray(activitiesData),
    [activitiesData],
  );

  const selectedCoach = useMemo(
    () => coaches.find((c) => c.id === selectedCoachId) || null,
    [coaches, selectedCoachId],
  );

  const detailsCoach = useMemo(() => detailsData?.data || null, [detailsData]);

  const filteredCoaches = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return coaches.filter((coach) => {
      const nameVal = coach.person?.full_name || "";
      const specVal = coach.details?.specialization || "";
      const phoneVal = coach.person?.phone || "";

      const matchesBranch =
        branchFilter === "all" || String(coach.branch_id) === String(branchFilter);
      const matchesEmployment =
        employmentFilter === "all" || coach.employment_type === employmentFilter;

      const matchesSearch =
        !normalizedSearch ||
        [nameVal, specVal, phoneVal]
          .filter(Boolean)
          .some((value) =>
            String(value).toLowerCase().includes(normalizedSearch),
          );

      return matchesBranch && matchesEmployment && matchesSearch;
    });
  }, [coaches, search, branchFilter, employmentFilter]);

  const stats = useMemo(() => {
    const activeCount = coaches.filter((c) => c.is_active).length;
    const fixedCount = coaches.filter((c) => c.employment_type === "fixed_salary").length;
    const commCount = coaches.filter((c) => c.employment_type === "commission" || c.employment_type === "hybrid").length;

    return [
      {
        title: "إجمالي المدربين",
        value: coaches.length.toLocaleString("ar"),
        helper: "المدربين المسجلين في النظام",
        tone: "yellow",
        compact: true,
      },
      {
        title: "المدربين النشطين",
        value: activeCount.toLocaleString("ar"),
        helper: "المدربين الذين يعملون حالياً",
        tone: "green",
        compact: true,
      },
      {
        title: "مدرب براتب ثابت",
        value: fixedCount.toLocaleString("ar"),
        helper: "موظفون براتب شهري ثابت",
        tone: "blue",
        compact: true,
      },
      {
        title: "عمولات أو هجين",
        value: commCount.toLocaleString("ar"),
        helper: "أجور نسبية أو هجينة",
        tone: "purple",
        compact: true,
      },
    ];
  }, [coaches]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedCoachId(null);
    setFormError("");
    setSelectedActivityId("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      const nameParts = (values.full_name || "").trim().split(" ");
      const first_name = nameParts[0] || "";
      const last_name = nameParts.slice(1).join(" ") || ".";

      let age = 25;
      if (values.dob) {
        const birthDate = new Date(values.dob);
        const today = new Date();
        let calculatedAge = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
          calculatedAge--;
        }
        if (!isNaN(calculatedAge) && calculatedAge >= 18 && calculatedAge <= 100) {
          age = calculatedAge;
        }
      }

      let employment_type = values.employment_type || "fixed_salary";
      if (employment_type === "commission") {
        employment_type = "commission_based";
      }

      const payload = {
        first_name,
        last_name,
        gender: values.gender || "male",
        age,
        dob: values.dob || null,
        phone_number: values.phone || null,
        country_code: values.country_code || "+963",
        email: values.email || null,
        address: values.address || null,
        branch_ids: values.branch_id ? [Number(values.branch_id)] : [],
        specialization: values.specialization || null,
        experience_years: Number(values.experience_years) || 0,
        employment_type,
        base_salary: Number(values.base_salary) || 0,
      };

      await createCoach(payload).unwrap();
      toast.success("تم إضافة المدرب بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Create coach validation/API error:", submitError);
      if (submitError?.data?.errors) {
        console.error("Detailed validation errors:", submitError.data.errors);
      }
      setFormError(
        submitError?.data?.message ||
          "تعذر إضافة المدرب. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdate(basicValues, detailsValues) {
    if (!selectedCoachId) return false;
    setFormError("");
    try {
      let employment_type = basicValues.employment_type || "fixed_salary";
      if (employment_type === "commission") {
        employment_type = "commission_based";
      }

      const mergedBody = {
        base_salary: Number(basicValues.base_salary) || 0,
        employment_type,
        is_active: basicValues.is_active,
        specialization: detailsValues.specialization || null,
        experience_years: Number(detailsValues.experience_years) || 0,
      };

      // The backend updates both basic info and details via the single PUT/PATCH endpoint
      await updateCoachBasic({
        id: selectedCoachId,
        body: mergedBody,
      }).unwrap();

      toast.success("تم تعديل بيانات المدرب بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Update coach validation/API error:", submitError);
      if (submitError?.data?.errors) {
        console.error("Detailed validation errors:", submitError.data.errors);
      }
      setFormError(
        submitError?.data?.message ||
          "تعذر تعديل بيانات المدرب. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  function handleDelete(coach) {
    setItemToDelete(coach);
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deleteCoach(itemToDelete.id).unwrap();
      toast.success("تم حذف المدرب بنجاح!");
    } catch {
      toast.error("تعذر حذف المدرب. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  async function handleAddActivity() {
    if (!selectedCoachId || !selectedActivityId) return;
    try {
      await addCoachActivity({
        id: selectedCoachId,
        activity_ids: [Number(selectedActivityId)],
      }).unwrap();
      toast.success("تم إسناد النشاط للمدرب بنجاح!");
      setSelectedActivityId("");
    } catch {
      toast.error("تعذر إسناد النشاط للمدرب.");
    }
  }

  async function handleRemoveActivity(activityId) {
    if (!selectedCoachId) return;
    try {
      await deleteCoachActivity({
        id: selectedCoachId,
        activityId,
      }).unwrap();
      toast.success("تم إلغاء إسناد النشاط من المدرب!");
    } catch {
      toast.error("تعذر إزالة النشاط من المدرب.");
    }
  }

  function getEditInitialValues() {
    if (!selectedCoach) return null;

    return {
      basic: {
        base_salary: Number(selectedCoach.base_salary) || 0,
        employment_type: selectedCoach.employment_type || "fixed_salary",
        is_active: selectedCoach.is_active !== false,
      },
      details: {
        specialization: selectedCoach.details?.specialization || "",
        experience_years: selectedCoach.details?.experience_years || 0,
      },
    };
  }

  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    employmentFilter,
    setEmploymentFilter,
    drawerMode,
    setDrawerMode,
    selectedCoachId,
    setSelectedCoachId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredCoaches,
    stats,
    selectedCoach,
    detailsCoach,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating: isUpdatingBasic || isUpdatingDetails,
    isDeleting,
    isAddingActivity,
    isDeletingActivity,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    handleAddActivity,
    handleRemoveActivity,
    selectedActivityId,
    setSelectedActivityId,
    getEditInitialValues,
    branches,
    activities,
    closeDrawer,
  };
}

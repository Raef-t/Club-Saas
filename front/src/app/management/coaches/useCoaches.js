import { useMemo, useState } from "react";
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

function getCoachesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getBranchesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getActivitiesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

export function useCoaches(params = {}) {
  const { selectedCoachId: initialSelectedId } = params;
  const [search, setSearch] = useState("");
  const [branchFilter, setBranchFilter] = useState("all");
  const [employmentFilter, setEmploymentFilter] = useState("all");
  const [activityFilter, setActivityFilter] = useState("all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedCoachId, setSelectedCoachId] = useState(initialSelectedId || null);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [selectedActivityId, setSelectedActivityId] = useState("");

  const queryParams = useMemo(() => {
    const params = {};
    if (branchFilter !== "all") params.branch_id = Number(branchFilter);
    if (activityFilter !== "all") params.activity_id = Number(activityFilter);
    return params;
  }, [branchFilter, activityFilter]);

  const { data, error, isLoading, refetch } = useGetCoachesQuery(queryParams);
  const { data: branchesData } = useGetBranchesQuery();
  const { data: activitiesData } = useGetActivitiesQuery();

  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetCoachQuery(selectedCoachId, {
    skip: !selectedCoachId || drawerMode !== "details",
  });

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
        branchFilter === "all" ||
        (Array.isArray(coach.branch_ids) &&
          coach.branch_ids.map(String).includes(String(branchFilter)));

      const matchesActivity =
        activityFilter === "all" ||
        (Array.isArray(coach.activities) &&
          coach.activities.some((act) => String(act.id) === String(activityFilter)));

      const matchesEmployment =
        employmentFilter === "all" || coach.employment_type === employmentFilter;

      const matchesSearch =
        !normalizedSearch ||
        [nameVal, specVal, phoneVal]
          .filter(Boolean)
          .some((value) =>
            String(value).toLowerCase().includes(normalizedSearch),
          );

      return matchesBranch && matchesActivity && matchesEmployment && matchesSearch;
    });
  }, [coaches, search, branchFilter, activityFilter, employmentFilter]);

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
        title: "نسبة أو هجين",
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
        branch_ids: Array.isArray(values.branch_ids) ? values.branch_ids.map(Number) : [],
        specialization: values.specialization || null,
        experience_years: Number(values.experience_years) || 0,
        employment_type: values.employment_type || "fixed_salary",
        base_salary: Number(values.base_salary) || 0,
        default_commission_rate: Number(values.default_commission_rate) || 0,
        work_types: Array.isArray(values.work_types) ? values.work_types : [],
        activity_ids: Array.isArray(values.activity_ids) ? values.activity_ids.map(Number) : [],
      };

      await createCoach(payload).unwrap();
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Create coach validation/API error:", submitError);
      setFormError(
        submitError?.data?.message ||
          "تعذر إضافة المدرب. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdate(basicValues, detailsValues) {
    if (!selectedCoachId) return;
    setFormError("");
    try {
      // 1. Update basic parameters
      await updateCoachBasic({
        id: selectedCoachId,
        body: basicValues,
      }).unwrap();

      // 2. Update specialization and experience details
      await updateCoachDetails({
        id: selectedCoachId,
        body: detailsValues,
      }).unwrap();

      closeDrawer();
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر تعديل بيانات المدرب. تحقق من البيانات وحاول مرة أخرى.",
      );
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
    } catch {
      window.alert("تعذر حذف المدرب. حاول مرة أخرى.");
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
      setSelectedActivityId("");
    } catch {
      window.alert("تعذر إسناد النشاط للمدرب.");
    }
  }

  async function handleRemoveActivity(activityId) {
    if (!selectedCoachId) return;
    try {
      await deleteCoachActivity({
        id: selectedCoachId,
        activityId,
      }).unwrap();
    } catch {
      window.alert("تعذر إزالة النشاط من المدرب.");
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
    activityFilter,
    setActivityFilter,
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

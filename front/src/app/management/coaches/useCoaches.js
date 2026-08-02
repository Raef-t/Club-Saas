import { useMemo, useState } from "react";
import {
  useGetCoachesQuery,
  useGetCoachQuery,
  useGetCoachShiftsQuery,
  useCreateCoachMutation,
  useUpdateCoachMutation,
  useDeleteCoachMutation,
  useAddCoachActivitiesMutation,
  useDeleteCoachActivityMutation,
} from "@/lib/api/coachesApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";

function getCoachesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getBranchesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getActivitiesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

/**
 * Coordinates coach data, filters, drawer state, and CRUD mutations.
 */
export function useCoaches(params = {}) {
  const { selectedCoachId: initialSelectedId, fetchDetails = false, initialData } = params;
  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
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
  const { data: activitiesData } = useGetActivitiesQuery(
    branchFilter === "all" ? {} : { branch_id: branchFilter },
  );

  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetCoachQuery(selectedCoachId, {
    skip: !selectedCoachId || (!fetchDetails && drawerMode !== "details"),
  });

  const { data: shiftsData, isFetching: isFetchingShifts } = useGetCoachShiftsQuery(
    selectedCoachId,
    {
      skip: !selectedCoachId || (!fetchDetails && drawerMode !== "details"),
    },
  );

  const [createCoach, { isLoading: isCreating }] = useCreateCoachMutation();
  const [updateCoach, { isLoading: isUpdating }] = useUpdateCoachMutation();
  const [deleteCoach, { isLoading: isDeleting }] = useDeleteCoachMutation();
  const [addCoachActivity, { isLoading: isAddingActivity }] = useAddCoachActivitiesMutation();
  const [deleteCoachActivity, { isLoading: isDeletingActivity }] = useDeleteCoachActivityMutation();

  const coaches = useMemo(
    () => getCoachesArray(data || initialData?.coaches),
    [data, initialData?.coaches],
  );
  const branches = useMemo(
    () => getBranchesArray(branchesData || initialData?.branches),
    [branchesData, initialData?.branches],
  );
  const allActivities = useMemo(
    () => getActivitiesArray(activitiesData || initialData?.activities),
    [activitiesData, initialData?.activities],
  );
  const activities = useMemo(
    () => filterEntitiesByBranch(allActivities, branchFilter),
    [allActivities, branchFilter],
  );

  const selectedCoach = useMemo(
    () => detailsData?.data || coaches.find((c) => c.id === selectedCoachId) || null,
    [coaches, selectedCoachId, detailsData],
  );

  const detailsCoach = useMemo(() => detailsData?.data || null, [detailsData]);
  const branchCoaches = useMemo(
    () => filterEntitiesByBranch(coaches, branchFilter),
    [branchFilter, coaches],
  );

  const filteredCoaches = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return branchCoaches.filter((coach) => {
      const nameVal = coach.person?.full_name || "";
      const specVal = coach.details?.specialization || "";
      const phoneVal =
        coach.person?.phone_number ||
        coach.person?.phone ||
        coach.person?.contacts?.[0]?.phone_number ||
        "";

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
          .some((value) => String(value).toLowerCase().includes(normalizedSearch));

      return matchesActivity && matchesEmployment && matchesSearch;
    });
  }, [activityFilter, branchCoaches, employmentFilter, search]);

  const stats = useMemo(() => {
    const activeCount = branchCoaches.filter((c) => c.is_active).length;
    const fixedCount = branchCoaches.filter((c) => c.employment_type === "fixed_salary").length;
    const commCount = branchCoaches.filter(
      (c) => c.employment_type === "commission" || c.employment_type === "hybrid",
    ).length;

    return [
      {
        title: "إجمالي المدربين",
        value: branchCoaches.length.toLocaleString("ar"),
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
  }, [branchCoaches]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedCoachId(null);
    setFormError("");
    setSelectedActivityId("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      const formData = new FormData();
      formData.append("first_name", values.first_name);
      formData.append("last_name", values.last_name);
      formData.append("gender", values.gender || "male");
      formData.append("dob", values.dob);
      if (values.phone_number) formData.append("phone_number", values.phone_number);
      formData.append("country_code", values.country_code || "+963");
      if (values.address) formData.append("address", values.address);

      if (Array.isArray(values.branch_ids)) {
        values.branch_ids.forEach((id) => formData.append("branch_ids[]", String(id)));
      }
      formData.append("experience_years", String(Number(values.experience_years) || 0));
      formData.append("is_active", values.is_active ? "1" : "0");
      formData.append("employment_type", values.employment_type || "fixed_salary");
      formData.append("base_salary", String(Number(values.base_salary) || 0));
      formData.append(
        "default_commission_rate",
        String(Number(values.default_commission_rate) || 0),
      );

      if (Array.isArray(values.work_types)) {
        values.work_types.forEach((type) => formData.append("work_types[]", type));
      }
      if (Array.isArray(values.activity_ids)) {
        values.activity_ids.forEach((id) => formData.append("activity_ids[]", String(id)));
      }
      if (Array.isArray(values.shifts)) {
        values.shifts.forEach((shift) => formData.append("shifts[]", String(shift)));
      }
      if (values.photo) {
        formData.append("photo", values.photo);
      }

      await createCoach(formData).unwrap();
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Create coach validation/API error:", submitError);
      setFormError(
        submitError?.data?.message || "تعذر إضافة المدرب. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedCoachId) return;
    setFormError("");
    try {
      const formData = new FormData();
      formData.append("_method", "PATCH"); // Laravel POST method spoofing
      formData.append("first_name", values.first_name);
      formData.append("last_name", values.last_name);
      formData.append("gender", values.gender || "male");
      formData.append("dob", values.dob);
      formData.append("phone_number", values.phone_number || "");
      formData.append("country_code", values.country_code || "+963");
      formData.append("address", values.address || "");

      if (Array.isArray(values.branch_ids)) {
        values.branch_ids.forEach((id) => formData.append("branch_ids[]", String(id)));
      }
      formData.append("experience_years", String(Number(values.experience_years) || 0));
      formData.append("is_active", values.is_active ? "1" : "0");
      formData.append("employment_type", values.employment_type || "fixed_salary");
      formData.append("base_salary", String(Number(values.base_salary) || 0));
      formData.append(
        "default_commission_rate",
        String(Number(values.default_commission_rate) || 0),
      );

      if (Array.isArray(values.work_types)) {
        values.work_types.forEach((type) => formData.append("work_types[]", type));
      }
      if (Array.isArray(values.activity_ids)) {
        values.activity_ids.forEach((id) => formData.append("activity_ids[]", String(id)));
      }
      if (Array.isArray(values.shifts)) {
        values.shifts.forEach((shift) => formData.append("shifts[]", String(shift)));
      }

      if (values.photo instanceof File) {
        formData.append("photo", values.photo);
      }

      await updateCoach({
        id: selectedCoachId,
        body: formData,
      }).unwrap();

      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Update coach validation/API error:", submitError);
      setFormError(
        submitError?.data?.message || "تعذر تعديل بيانات المدرب. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  function getEditInitialValues() {
    if (!selectedCoach || (fetchDetails && (!detailsData || isFetchingShifts))) return null;

    const branchIds = Array.isArray(selectedCoach.branch_ids)
      ? selectedCoach.branch_ids.map(Number)
      : Array.isArray(selectedCoach.branches)
        ? selectedCoach.branches.map((b) => Number(b.id))
        : [];
    const activityIds = Array.isArray(selectedCoach.activities)
      ? selectedCoach.activities.map((a) => Number(a.id))
      : [];

    const fetchedShifts = Array.isArray(shiftsData?.data)
      ? shiftsData.data
      : Array.isArray(shiftsData)
        ? shiftsData
        : [];
    const shiftIdsFromApi = fetchedShifts
      .map((s) => Number(s.branch_shift_id || s.branch_shift?.id || s.id))
      .filter((id) => !isNaN(id) && id > 0);

    const shiftIds =
      shiftIdsFromApi.length > 0
        ? shiftIdsFromApi
        : Array.isArray(selectedCoach.shifts)
          ? selectedCoach.shifts
              .map((s) => Number(s.branch_shift_id || s.branch_shift?.id || s.id))
              .filter((id) => !isNaN(id) && id > 0)
          : [];
    const workTypes = selectedCoach.work_types || selectedCoach.details?.work_types || [];
    const [firstName, ...remainingNameParts] = (selectedCoach.person?.full_name || "").split(/\s+/);
    const primaryContact = selectedCoach.person?.contacts?.[0] || null;

    return {
      first_name: selectedCoach.person?.first_name || firstName || "",
      last_name:
        selectedCoach.person?.last_name || remainingNameParts.join(" ") || "",
      gender: selectedCoach.person?.gender || "male",
      dob: selectedCoach.person?.dob ? selectedCoach.person.dob.split("T")[0] : "",
      phone_number:
        selectedCoach.person?.phone_number ||
        selectedCoach.person?.phone ||
        primaryContact?.phone_number ||
        "",
      country_code:
        selectedCoach.person?.country_code || primaryContact?.country_code || "+963",
      address: selectedCoach.person?.address || "",
      branch_ids: branchIds,
      experience_years: String(
        selectedCoach.experience_years || selectedCoach.details?.experience_years || 0,
      ),
      is_active: selectedCoach.is_active ?? true,
      employment_type: selectedCoach.employment_type || "fixed_salary",
      base_salary: String(Number(selectedCoach.base_salary) || 0),
      default_commission_rate: String(Number(selectedCoach.details?.default_commission_rate) || 0),
      work_types: Array.isArray(workTypes) ? workTypes : [],
      activity_ids: activityIds,
      shift_ids: shiftIds,
      photo: selectedCoach.person?.photo_url || selectedCoach.person?.photo || null,
    };
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
    isUpdating,
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

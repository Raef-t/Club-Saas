"use client";

import { useMemo, useState } from "react";
import {
  useGetCoachesQuery,
  useGetCoachQuery,
  useCreateCoachMutation,
  useUpdateCoachMutation,
  useUpdateCoachPhotoMutation,
  useDeleteCoachMutation,
} from "@/lib/api/coachesApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { resolveWorkStatus } from "@/lib/workStatus";
import { createCoachActivityPlansMap } from "./coachDetailsUtils";
import { createCoachEditInitialValues } from "./coachFormUtils";

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
  const [workStatusFilter, setWorkStatusFilter] = useState("all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedCoachId, setSelectedCoachId] = useState(initialSelectedId || null);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const queryParams = useMemo(() => {
    const params = {};
    if (branchFilter !== "all") params.branch_id = Number(branchFilter);
    if (activityFilter !== "all") params.activity_id = Number(activityFilter);
    if (workStatusFilter !== "all") params.work_status = workStatusFilter;
    return params;
  }, [activityFilter, branchFilter, workStatusFilter]);

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

  const [createCoach, { isLoading: isCreating }] = useCreateCoachMutation();
  const [updateCoach, { isLoading: isUpdating }] = useUpdateCoachMutation();
  const [updateCoachPhoto] = useUpdateCoachPhotoMutation();
  const [deleteCoach, { isLoading: isDeleting }] = useDeleteCoachMutation();

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

  const { data: plansData } = useGetSubscriptionPlansQuery(
    branchFilter === "all" ? {} : { branch_id: branchFilter },
  );

  const coachActivityPlansMap = useMemo(() => {
    const plans = Array.isArray(plansData?.data) ? plansData.data : [];
    return createCoachActivityPlansMap(plans);
  }, [plansData]);

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
      const activitiesVal = Array.isArray(coach.activities)
        ? coach.activities.map((a) => a.name || "").join(" ")
        : "";
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
      const matchesWorkStatus =
        workStatusFilter === "all" || resolveWorkStatus(coach) === workStatusFilter;

      const matchesSearch =
        !normalizedSearch ||
        [nameVal, activitiesVal, phoneVal, coach.qr_code, coach.username]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(normalizedSearch));

      return matchesActivity && matchesEmployment && matchesWorkStatus && matchesSearch;
    });
  }, [activityFilter, branchCoaches, employmentFilter, search, workStatusFilter]);

  const stats = useMemo(() => {
    const activeCount = branchCoaches.filter(
      (coach) => resolveWorkStatus(coach) === "active",
    ).length;
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
      if (values.start_date) formData.append("start_date", values.start_date);
      formData.append("work_status", values.work_status);
      formData.append("is_active", values.work_status === "active" ? "1" : "0");
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

      const response = await createCoach(formData).unwrap();
      closeDrawer();
      return response;
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
      formData.append("start_date", values.start_date || "");
      formData.append("work_status", values.work_status);
      formData.append("is_active", values.work_status === "active" ? "1" : "0");
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

      await updateCoach({
        id: selectedCoachId,
        body: formData,
      }).unwrap();

      if (values.photo instanceof File) {
        const photoFormData = new FormData();
        photoFormData.append("photo", values.photo);
        await updateCoachPhoto({ id: selectedCoachId, body: photoFormData }).unwrap();
      }

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
    if (!selectedCoach || (fetchDetails && !detailsData)) return null;
    return createCoachEditInitialValues(selectedCoach);
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

  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    employmentFilter,
    setEmploymentFilter,
    activityFilter,
    setActivityFilter,
    workStatusFilter,
    setWorkStatusFilter,
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
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    getEditInitialValues,
    branches,
    activities,
    coachActivityPlansMap,
    closeDrawer,
  };
}

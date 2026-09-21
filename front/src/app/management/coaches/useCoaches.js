import { useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
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
import { getPaginationMeta, useServerPagination, withAllItems } from "@/lib/pagination";

function getCoachesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getBranchesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getActivitiesArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

const COACH_EMPLOYMENT_FILTER_ALIASES = {
  commission: "commission_based",
  راتب: "fixed_salary",
  "راتب ثابت": "fixed_salary",
  نسبة: "commission_based",
  "نسبة فقط": "commission_based",
  "نسبة وراتب": "hybrid",
  "راتب ونسبة": "hybrid",
};

export function normalizeCoachEmploymentFilter(value) {
  const normalized = COACH_EMPLOYMENT_FILTER_ALIASES[String(value || "").trim()] || value;
  return ["fixed_salary", "commission_based", "hybrid"].includes(normalized) ? normalized : "all";
}

function normalizeBranchIds(branchIds, selectedBranchId = "all") {
  const normalized = (Array.isArray(branchIds) ? branchIds : [])
    .map(Number)
    .filter((id) => Number.isFinite(id) && id > 0);

  if (normalized.length > 0) return [...new Set(normalized)];

  const selectedId = Number(selectedBranchId);
  return selectedBranchId !== "all" && Number.isFinite(selectedId) && selectedId > 0
    ? [selectedId]
    : [];
}

export function createCoachUpdatePayload(values, selectedBranchId = "all") {
  return {
    first_name: values.first_name.trim(),
    last_name: values.last_name.trim(),
    gender: values.gender || "male",
    dob: values.dob || null,
    phone_number: values.phone_number?.trim() || null,
    country_code: values.country_code?.trim() || "+963",
    address: values.address?.trim() || null,
    branch_ids: normalizeBranchIds(values.branch_ids, selectedBranchId),
    experience_years: Number(values.experience_years) || 0,
    start_date: values.start_date || null,
    work_status: values.work_status,
    is_active: values.work_status === "active",
    employment_type: values.employment_type || "fixed_salary",
    base_salary: Number(values.base_salary) || 0,
    default_commission_rate: Number(values.default_commission_rate) || 0,
    private_commission_rate: Number(values.private_commission_rate) || 0,
    reason: values.reason?.trim() || "",
    work_types: Array.isArray(values.work_types) ? values.work_types : [],
    activity_ids: normalizeBranchIds(values.activity_ids),
    shifts: normalizeBranchIds(values.shifts),
  };
}

/**
 * Coordinates coach data, filters, drawer state, and CRUD mutations.
 */
export function useCoaches(params = {}) {
  const { selectedCoachId: initialSelectedId, fetchDetails = false, initialData } = params;
  const searchParams = useSearchParams();
  const urlWorkStatus = searchParams?.get("work_status") || searchParams?.get("status");
  const urlEmployment = searchParams?.get("employment_type") || searchParams?.get("employment");
  const urlActivity = searchParams?.get("activity_id") || searchParams?.get("activity");

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [employmentFilter, setEmploymentFilter] = useState(() =>
    normalizeCoachEmploymentFilter(urlEmployment),
  );
  const [activityFilter, setActivityFilter] = useState(urlActivity || "all");
  const [workStatusFilter, setWorkStatusFilter] = useState(urlWorkStatus || "all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedCoachId, setSelectedCoachId] = useState(initialSelectedId || null);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const paginationFilterKey = [
    branchFilter,
    activityFilter,
    workStatusFilter,
    employmentFilter,
    search,
  ].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);

  const queryParams = useMemo(() => {
    const params = { page, per_page: perPage };
    const normalizedEmployment = normalizeCoachEmploymentFilter(employmentFilter);
    if (branchFilter !== "all") params.branch_id = Number(branchFilter);
    if (activityFilter !== "all") params.activity_id = Number(activityFilter);
    if (workStatusFilter !== "all") params.work_status = workStatusFilter;
    if (normalizedEmployment !== "all") params.employment_type = normalizedEmployment;
    if (search.trim()) params.search = search.trim();
    return params;
  }, [activityFilter, branchFilter, employmentFilter, page, perPage, search, workStatusFilter]);

  const {
    currentData: data,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetCoachesQuery(queryParams);
  const { data: branchesData } = useGetBranchesQuery(withAllItems());
  const { data: activitiesData } = useGetActivitiesQuery(
    withAllItems(branchFilter === "all" ? {} : { branch_id: branchFilter }),
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

  const canUseInitialCoaches =
    page === 1 &&
    perPage === 15 &&
    branchFilter === "all" &&
    activityFilter === "all" &&
    workStatusFilter === "all" &&
    employmentFilter === "all" &&
    !search.trim();
  const coachesResponse = data || (canUseInitialCoaches ? initialData?.coaches : null);
  const coaches = useMemo(() => getCoachesArray(coachesResponse), [coachesResponse]);
  const pagination = useMemo(
    () => getPaginationMeta(coachesResponse, { page, perPage }),
    [coachesResponse, page, perPage],
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
    withAllItems(branchFilter === "all" ? {} : { branch_id: branchFilter }),
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

  const filteredCoaches = branchCoaches;
  const totalResults = pagination.total;

  const stats = useMemo(() => {
    const activeCount = branchCoaches.filter(
      (coach) => resolveWorkStatus(coach) === "active",
    ).length;
    const fixedCount = branchCoaches.filter((c) => c.employment_type === "fixed_salary").length;
    const commCount = branchCoaches.filter(
      (c) => c.employment_type === "commission_based" || c.employment_type === "commission",
    ).length;

    return [
      {
        title: "إجمالي المدربين",
        value: branchCoaches.length.toLocaleString("ar"),
        helper: "المدربين المسجلين في النظام",
        tone: "yellow",
        compact: true,
        onClick: () => {
          setWorkStatusFilter("all");
          setEmploymentFilter("all");
          setActivityFilter("all");
        },
        active:
          workStatusFilter === "all" && employmentFilter === "all" && activityFilter === "all",
      },
      {
        title: "المدربين النشطين",
        value: activeCount.toLocaleString("ar"),
        helper: "المدربين الذين يعملون حالياً",
        tone: "green",
        compact: true,
        onClick: () => setWorkStatusFilter(workStatusFilter === "active" ? "all" : "active"),
        active: workStatusFilter === "active",
      },
      {
        title: "مدرب براتب ثابت",
        value: fixedCount.toLocaleString("ar"),
        helper: "موظفون براتب شهري ثابت",
        tone: "blue",
        compact: true,
        onClick: () =>
          setEmploymentFilter(employmentFilter === "fixed_salary" ? "all" : "fixed_salary"),
        active: employmentFilter === "fixed_salary",
      },
      {
        title: "نسبة فقط",
        value: commCount.toLocaleString("ar"),
        helper: "مدربون يعملون بنظام النسبة",
        tone: "purple",
        compact: true,
        onClick: () =>
          setEmploymentFilter(employmentFilter === "commission_based" ? "all" : "commission_based"),
        active: employmentFilter === "commission_based",
      },
    ];
  }, [activityFilter, branchCoaches, employmentFilter, workStatusFilter]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedCoachId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      const branchIds = normalizeBranchIds(values.branch_ids, branchFilter);
      if (branchIds.length === 0) {
        setFormError("يرجى اختيار فرع واحد على الأقل.");
        return false;
      }

      const formData = new FormData();
      formData.append("first_name", values.first_name);
      formData.append("last_name", values.last_name);
      formData.append("gender", values.gender || "male");
      formData.append("dob", values.dob);
      const phoneNumber = values.phone_number?.trim();
      if (phoneNumber) formData.append("phone_number", phoneNumber);
      formData.append("country_code", values.country_code?.trim() || "+963");
      if (values.address) formData.append("address", values.address);

      branchIds.forEach((id) => formData.append("branch_ids[]", String(id)));
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
      formData.append(
        "private_commission_rate",
        String(Number(values.private_commission_rate) || 0),
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
      const body = createCoachUpdatePayload(values, branchFilter);
      if (body.branch_ids.length === 0) {
        setFormError("يرجى اختيار فرع واحد على الأقل.");
        return false;
      }

      await updateCoach({
        id: selectedCoachId,
        body,
      }).unwrap();

      if (values.photoChanged) {
        const photoFormData = new FormData();
        if (values.photo instanceof File) {
          photoFormData.append("photo", values.photo);
        } else {
          // في حالة حذف الصورة
          photoFormData.append("delete_photo", "1");
        }
        await updateCoachPhoto({ id: selectedCoachId, body: photoFormData }).unwrap();
      } else if (values.photo instanceof File) {
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
    setDeleteConfirmation("");
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
    setDeleteConfirmation("");
  }

  async function confirmDelete() {
    if (!itemToDelete || deleteConfirmation !== "delete") return;
    try {
      await deleteCoach({
        id: itemToDelete.id,
        confirmation: deleteConfirmation,
      }).unwrap();
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
    isLoading: isLoading || (isFetching && !coachesResponse),
    error,
    refetch,
    filteredCoaches,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
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
    deleteConfirmation,
    setDeleteConfirmation,
    getEditInitialValues,
    branches,
    activities,
    coachActivityPlansMap,
    closeDrawer,
  };
}

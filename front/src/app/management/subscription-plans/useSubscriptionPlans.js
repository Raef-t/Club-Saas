import { useEffect, useMemo, useState } from "react";
import {
  useCreateSubscriptionPlanMutation,
  useDeleteSubscriptionPlanMutation,
  useGetSubscriptionPlanQuery,
  useGetSubscriptionPlansQuery,
  useUpdateSubscriptionPlanMutation,
} from "@/lib/api/subscriptionPlansApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useToast } from "@/components/ui/Toast";
import { getBranchesArray } from "@/lib/utils";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";

import { formatMoney as baseFormatMoney, formatLocalizedName } from "@/lib/utils";
//test
function parseAmount(value) {
  const number = Number.parseFloat(value || 0);
  return Number.isFinite(number) ? number : 0;
}

function formatMoney(value) {
  return baseFormatMoney(value, "$");
}

function getPlans(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getPlanDetails(response) {
  return response?.data || null;
}

const planName = (plan) => formatLocalizedName(plan?.name);

export function useSubscriptionPlans({
  selectedPlanId: initialSelectedPlanId = null,
  initialData,
} = {}) {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedPlanId, setSelectedPlanId] = useState(initialSelectedPlanId);
  const [formError, setFormError] = useState("");

  const branchQueryParams = selectedBranchId === "all" ? {} : { branch_id: selectedBranchId };
  const { data, error, isLoading, refetch } = useGetSubscriptionPlansQuery(branchQueryParams);
  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
    isLoading: isLoadingDetails,
  } = useGetSubscriptionPlanQuery(selectedPlanId, {
    skip: !selectedPlanId,
  });

  const { data: branchesData, error: branchesError } = useGetBranchesQuery();
  const branches = useMemo(
    () => getBranchesArray(branchesData || initialData?.branches),
    [branchesData, initialData?.branches],
  );

  const { data: activitiesData } = useGetActivitiesQuery(branchQueryParams);
  const allActivities = useMemo(() => {
    const response = activitiesData || initialData?.activities;
    return Array.isArray(response?.data) ? response.data : [];
  }, [activitiesData, initialData?.activities]);
  const activities = useMemo(
    () => filterEntitiesByBranch(allActivities, selectedBranchId),
    [allActivities, selectedBranchId],
  );

  const { data: coachesData } = useGetCoachesQuery(branchQueryParams);
  const allCoaches = useMemo(() => {
    const response = coachesData || initialData?.coaches;
    return Array.isArray(response?.data) ? response.data : [];
  }, [coachesData, initialData?.coaches]);
  const coaches = useMemo(
    () => filterEntitiesByBranch(allCoaches, selectedBranchId),
    [allCoaches, selectedBranchId],
  );

  useEffect(() => {
    if (error) {
      console.warn("[useSubscriptionPlans] Error fetching subscription plans:", error);
    }
    if (detailsError) {
      console.warn(
        "[useSubscriptionPlans] Error fetching subscription plan details:",
        detailsError,
      );
    }
    if (branchesError) {
      console.warn("[useSubscriptionPlans] Error fetching branches:", branchesError);
    }
  }, [error, detailsError, branchesError]);

  const [createPlan, { isLoading: isCreating }] = useCreateSubscriptionPlanMutation();
  const [updatePlan, { isLoading: isUpdating }] = useUpdateSubscriptionPlanMutation();
  const [deletePlan, { isLoading: isDeleting }] = useDeleteSubscriptionPlanMutation();

  const allPlans = useMemo(() => getPlans(data || initialData?.plans), [data, initialData?.plans]);
  const plans = useMemo(
    () => filterEntitiesByBranch(allPlans, selectedBranchId),
    [allPlans, selectedBranchId],
  );
  const selectedPlan = useMemo(
    () => plans.find((plan) => plan.id === selectedPlanId) || null,
    [plans, selectedPlanId],
  );
  const detailsPlan = useMemo(() => getPlanDetails(detailsData), [detailsData]);

  const filteredPlans = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();
    if (!normalizedSearch) return plans;

    return plans.filter((plan) =>
      [plan.name?.ar, plan.name?.en, plan.type, plan.base_price]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(normalizedSearch)),
    );
  }, [plans, search]);

  const stats = useMemo(() => {
    const activeCount = plans.filter((plan) => plan.status === "active" || plan.is_active).length;
    const averagePrice = plans.length
      ? plans.reduce((sum, plan) => sum + parseAmount(plan.base_price), 0) / plans.length
      : 0;

    return [
      {
        title: "إجمالي الخطط",
        value: plans.length.toLocaleString("ar"),
        helper: "كل الخطط المتاحة",
        tone: "yellow",
        compact: true,
      },
      {
        title: "الخطط الفعالة",
        value: activeCount.toLocaleString("ar"),
        helper: "جاهزة للاستخدام",
        tone: "green",
        compact: true,
      },
      {
        title: "متوسط السعر",
        value: formatMoney(averagePrice),
        helper: "حسب أسعار الخطط",
        tone: "blue",
        compact: true,
      },
      {
        title: "أكثر جلسات أسبوعياً",
        value: `${Math.max(0, ...plans.map((plan) => plan.sessions_per_week || 0)).toLocaleString("ar")} جلسة`,
        helper: "أكثر عدد جلسات أسبوعية",
        tone: "purple",
        compact: true,
      },
    ];
  }, [plans]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedPlanId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");

    const apiPayload = {
      ...values,
      base_price: values.price,
      status: values.is_active ? "active" : "inactive",
    };
    delete apiPayload.price;
    delete apiPayload.is_active;

    try {
      await createPlan(apiPayload).unwrap();
      toast.success("تم إنشاء خطة الاشتراك بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر إنشاء الخطة. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedPlanId) return false;
    setFormError("");

    const apiPayload = {
      ...values,
      base_price: values.price,
      status: values.is_active ? "active" : "inactive",
    };
    delete apiPayload.price;
    delete apiPayload.is_active;

    try {
      await updatePlan({ id: selectedPlanId, body: apiPayload }).unwrap();
      toast.success("تم تعديل خطة الاشتراك بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر تعديل الخطة. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  function handleDelete(plan) {
    setItemToDelete(plan);
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deletePlan(itemToDelete.id).unwrap();
      toast.success("تم حذف خطة الاشتراك بنجاح!");
    } catch {
      toast.error("تعذر حذف الخطة. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  function getEditInitialValues() {
    const plan = detailsPlan || selectedPlan;
    if (!plan) return null;

    return {
      branch_id: plan.branch_id ? String(plan.branch_id) : "",
      name: planName(plan) === "-" ? "" : planName(plan),
      sessions_per_week: plan.sessions_per_week ? String(plan.sessions_per_week) : "",
      session_count: plan.session_count ? String(plan.session_count) : "",
      price: String(parseAmount(plan.base_price || "")),
      max_subscribers: String(plan.max_subscribers ?? "0"),
      is_active: plan.status === "active" || plan.is_active === true,
      gender_restriction: plan.gender_restriction || "mixed",
      is_unlimited_subscribers: !!plan.is_unlimited_subscribers,
      activities:
        plan.activities?.map((a) => ({
          activity_id: String(a.activity_id),
          coach_id: String(a.coach_id),
        })) || [],
      session_templates:
        plan.session_templates?.map((s) => ({
          day_of_week: String(s.day_of_week),
          start_time: s.start_time || "",
          end_time: s.end_time || "",
        })) || [],
    };
  }

  return {
    search,
    setSearch,
    drawerMode,
    setDrawerMode,
    selectedPlanId,
    setSelectedPlanId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredPlans,
    stats,
    selectedPlan,
    detailsPlan,
    isFetchingDetails,
    isLoadingDetails,
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
    branches,
    activities,
    coaches,
  };
}

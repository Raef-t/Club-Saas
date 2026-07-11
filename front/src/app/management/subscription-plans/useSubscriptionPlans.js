import { useEffect, useMemo, useState } from "react";
import {
  useCreateSubscriptionPlanMutation,
  useDeleteSubscriptionPlanMutation,
  useGetSubscriptionPlanQuery,
  useGetSubscriptionPlansQuery,
  useUpdateSubscriptionPlanMutation,
} from "@/lib/api/subscriptionPlansApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useToast } from "@/components/ui/Toast";

import {
  formatMoney as baseFormatMoney,
  formatLocalizedName,
} from "@/lib/utils";

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
} = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedPlanId, setSelectedPlanId] = useState(initialSelectedPlanId);
  const [formError, setFormError] = useState("");

  const { data, error, isLoading, refetch } = useGetSubscriptionPlansQuery();
  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
    isLoading: isLoadingDetails,
  } = useGetSubscriptionPlanQuery(selectedPlanId, {
    skip: !selectedPlanId || drawerMode !== "details",
  });

  const { data: branchesData, error: branchesError } = useGetBranchesQuery();
  const branches = useMemo(() => branchesData?.data || [], [branchesData]);

  useEffect(() => {
    if (error) {
      console.warn("[useSubscriptionPlans] Error fetching subscription plans:", error);
    }
    if (detailsError) {
      console.warn("[useSubscriptionPlans] Error fetching subscription plan details:", detailsError);
    }
    if (branchesError) {
      console.warn("[useSubscriptionPlans] Error fetching branches:", branchesError);
    }
  }, [error, detailsError, branchesError]);

  const [createPlan, { isLoading: isCreating }] =
    useCreateSubscriptionPlanMutation();
  const [updatePlan, { isLoading: isUpdating }] =
    useUpdateSubscriptionPlanMutation();
  const [deletePlan, { isLoading: isDeleting }] =
    useDeleteSubscriptionPlanMutation();

  const plans = useMemo(() => getPlans(data), [data]);
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
        .some((value) =>
          String(value).toLowerCase().includes(normalizedSearch),
        ),
    );
  }, [plans, search]);

  const stats = useMemo(() => {
    const activeCount = plans.filter((plan) => plan.is_active).length;
    const averagePrice = plans.length
      ? plans.reduce((sum, plan) => sum + parseAmount(plan.base_price), 0) /
        plans.length
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
        title: "أكثر مدة",
        value: `${Math.max(0, ...plans.map((plan) => plan.duration_days || 0)).toLocaleString("ar")} يوم`,
        helper: "أطول خطة متاحة",
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
      duration_days: values.duration_in_days,
      base_price: values.price,
    };
    delete apiPayload.duration_in_days;
    delete apiPayload.price;

    try {
      await createPlan(apiPayload).unwrap();
      toast.success("تم إنشاء خطة الاشتراك بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر إنشاء الخطة. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedPlanId) return false;
    setFormError("");

    const apiPayload = {
      ...values,
      duration_days: values.duration_in_days,
      base_price: values.price,
    };
    delete apiPayload.duration_in_days;
    delete apiPayload.price;

    try {
      await updatePlan({ id: selectedPlanId, body: apiPayload }).unwrap();
      toast.success("تم تعديل خطة الاشتراك بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر تعديل الخطة. تحقق من البيانات وحاول مرة أخرى.",
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
    if (!selectedPlan) return null;

    return {
      branch_id: selectedPlan.branch_id ? String(selectedPlan.branch_id) : "",
      name: planName(selectedPlan) === "-" ? "" : planName(selectedPlan),
      type: selectedPlan.type || "fixed_period",
      duration_in_days: String(selectedPlan?.duration_days ?? ""),
      session_count: selectedPlan.session_count ? String(selectedPlan.session_count) : "",
      price: String(parseAmount(selectedPlan?.base_price || "")),
      max_freeze_count: String(selectedPlan?.max_freeze_count ?? "0"),
      max_freeze_days: String(selectedPlan?.max_freeze_days ?? "0"),
      max_subscribers: String(selectedPlan?.max_subscribers ?? "0"),
      is_active: selectedPlan?.is_active ?? true,
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
  };
}

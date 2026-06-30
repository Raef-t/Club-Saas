import { useMemo, useState } from "react";
import {
  useCreateSubscriptionPlanMutation,
  useDeleteSubscriptionPlanMutation,
  useGetSubscriptionPlanQuery,
  useGetSubscriptionPlansQuery,
  useUpdateSubscriptionPlanMutation,
} from "@/lib/api/subscriptionPlansApi";
import { useToast } from "@/components/ui/Toast";

function parseAmount(value) {
  const number = Number.parseFloat(value || 0);
  return Number.isFinite(number) ? number : 0;
}

function formatMoney(value) {
  return `$${parseAmount(value).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}`;
}

function getPlans(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getPlanDetails(response) {
  return response?.data || null;
}

function planName(plan) {
  return plan?.name?.ar || plan?.name?.en || "-";
}

export function useSubscriptionPlans() {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedPlanId, setSelectedPlanId] = useState(null);
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
        .some((value) => String(value).toLowerCase().includes(normalizedSearch)),
    );
  }, [plans, search]);

  const stats = useMemo(() => {
    const activeCount = plans.filter((plan) => plan.is_active).length;
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

    try {
      await createPlan(values).unwrap();
      toast.success("تم إنشاء خطة الاشتراك بنجاح!");
      closeDrawer();
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر إنشاء الخطة. تحقق من البيانات وحاول مرة أخرى.",
      );
    }
  }

  async function handleUpdate(values) {
    if (!selectedPlanId) return;
    setFormError("");

    try {
      await updatePlan({ id: selectedPlanId, body: values }).unwrap();
      toast.success("تم تعديل خطة الاشتراك بنجاح!");
      closeDrawer();
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر تعديل الخطة. تحقق من البيانات وحاول مرة أخرى.",
      );
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
    return {
      name: planName(selectedPlan) === "-" ? "" : planName(selectedPlan),
      duration_in_days: String(selectedPlan?.duration_days || 30),
      price: String(parseAmount(selectedPlan?.base_price || "")),
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
  };
}

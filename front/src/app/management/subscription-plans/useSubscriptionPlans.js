import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import {
  useCreateSubscriptionPlanMutation,
  useDeleteSubscriptionPlanMutation,
  useGetSubscriptionPlanPlayersQuery,
  useGetSubscriptionPlanQuery,
  useGetSubscriptionPlansQuery,
  useResumeSubscriptionPlanMutation,
  useSuspendSubscriptionPlanMutation,
  useUpdateSubscriptionPlanMutation,
} from "@/lib/api/subscriptionPlansApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useToast } from "@/components/ui/Toast";
import { getBranchesArray } from "@/lib/utils";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { getPaginationMeta, useServerPagination, withAllItems } from "@/lib/pagination";

import { CURRENCY_SYMBOL, formatMoney as baseFormatMoney, formatLocalizedName } from "@/lib/utils";
import {
  getSubscriptionPlanStatus,
  isSubscriptionPlanActive,
  SUBSCRIPTION_PLAN_STATUS,
} from "./subscriptionPlanStatus";
import { getSubscriptionPlanSuspensionId } from "./subscriptionPlanSuspension";
//test
//testtest
function parseAmount(value) {
  const number = Number.parseFloat(value || 0);
  return Number.isFinite(number) ? number : 0;
}

function formatMoney(value) {
  return baseFormatMoney(value, CURRENCY_SYMBOL);
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
  const searchParams = useSearchParams();
  const urlStatus = searchParams?.get("status") || searchParams?.get("active");
  const { selectedBranchId } = useManagementBranch();
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState(urlStatus || "all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedPlanId, setSelectedPlanId] = useState(initialSelectedPlanId);
  const [formError, setFormError] = useState("");

  const branchQueryParams = useMemo(
    () => (selectedBranchId === "all" ? {} : { branch_id: selectedBranchId }),
    [selectedBranchId],
  );
  const paginationFilterKey = [selectedBranchId, statusFilter, search].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);
  const needsAllPlans =
    Boolean(search.trim()) || !["all", "active"].includes(statusFilter);
  const listQueryParams = useMemo(
    () => ({
      ...branchQueryParams,
      ...(statusFilter === "active" ? { status: "active" } : {}),
      ...(needsAllPlans ? { per_page: "all" } : { page, per_page: perPage }),
    }),
    [branchQueryParams, needsAllPlans, page, perPage, statusFilter],
  );
  const { currentData: data, error, isLoading, isFetching, refetch } =
    useGetSubscriptionPlansQuery(listQueryParams);
  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
    isLoading: isLoadingDetails,
  } = useGetSubscriptionPlanQuery(selectedPlanId, {
    skip: !selectedPlanId,
  });
  const {
    data: playersData,
    error: playersError,
    isFetching: isFetchingPlayers,
    isLoading: isLoadingPlayers,
    refetch: refetchPlayers,
  } = useGetSubscriptionPlanPlayersQuery(selectedPlanId, {
    skip: !selectedPlanId || drawerMode !== "details",
    refetchOnMountOrArgChange: true,
  });

  const { data: branchesData, error: branchesError } = useGetBranchesQuery(withAllItems());
  const branches = useMemo(
    () => getBranchesArray(branchesData || initialData?.branches),
    [branchesData, initialData?.branches],
  );

  const { data: activitiesData } = useGetActivitiesQuery(withAllItems(branchQueryParams));
  const allActivities = useMemo(() => {
    const response = activitiesData || initialData?.activities;
    return Array.isArray(response?.data) ? response.data : [];
  }, [activitiesData, initialData?.activities]);
  const activities = useMemo(
    () => filterEntitiesByBranch(allActivities, selectedBranchId),
    [allActivities, selectedBranchId],
  );

  const { data: coachesData } = useGetCoachesQuery(withAllItems(branchQueryParams));
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
    if (playersError) {
      console.warn(
        "[useSubscriptionPlans] Error fetching subscription plan players:",
        playersError,
      );
    }
  }, [error, detailsError, branchesError, playersError]);

  const [createPlan, { isLoading: isCreating }] = useCreateSubscriptionPlanMutation();
  const [updatePlan, { isLoading: isUpdating }] = useUpdateSubscriptionPlanMutation();
  const [deletePlan, { isLoading: isDeleting }] = useDeleteSubscriptionPlanMutation();
  const [suspendPlan, { isLoading: isSuspending }] = useSuspendSubscriptionPlanMutation();
  const [resumePlan, { isLoading: isResuming }] = useResumeSubscriptionPlanMutation();

  const canUseInitialPlans =
    !needsAllPlans &&
    page === 1 &&
    perPage === 15 &&
    selectedBranchId === "all" &&
    statusFilter === "all";
  const listResponse = data || (canUseInitialPlans ? initialData?.plans : null);
  const allPlans = useMemo(() => getPlans(listResponse), [listResponse]);
  const pagination = useMemo(
    () => getPaginationMeta(listResponse, { page, perPage }),
    [listResponse, page, perPage],
  );
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

    return plans.filter((plan) => {
      const matchesStatus =
        statusFilter === "all" ||
        (statusFilter === "active"
          ? isSubscriptionPlanActive(plan)
          : !isSubscriptionPlanActive(plan));
      const matchesSearch =
        !normalizedSearch ||
        [plan.name?.ar, plan.name?.en, plan.type, plan.base_price]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(normalizedSearch));

      return matchesStatus && matchesSearch;
    });
  }, [plans, search, statusFilter]);
  const totalResults = needsAllPlans ? filteredPlans.length : pagination.total;

  const stats = useMemo(() => {
    const activeCount = plans.filter(isSubscriptionPlanActive).length;
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
        onClick: () => setStatusFilter("all"),
        active: statusFilter === "all",
      },
      {
        title: "الخطط الفعالة",
        value: activeCount.toLocaleString("ar"),
        helper: "جاهزة للاستخدام",
        tone: "green",
        compact: true,
        onClick: () => setStatusFilter(statusFilter === "active" ? "all" : "active"),
        active: statusFilter === "active",
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
  }, [plans, statusFilter]);

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
      status: values.is_active
        ? SUBSCRIPTION_PLAN_STATUS.ACTIVE
        : SUBSCRIPTION_PLAN_STATUS.INACTIVE,
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
      status:
        values.status ||
        (values.is_active ? SUBSCRIPTION_PLAN_STATUS.ACTIVE : SUBSCRIPTION_PLAN_STATUS.INACTIVE),
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
  const [deleteConfirmation, setDeleteConfirmation] = useState("");

  function handleDelete(plan) {
    setItemToDelete(plan);
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
      await deletePlan(itemToDelete.id).unwrap();
      toast.success("تم حذف خطة الاشتراك بنجاح!");
    } catch {
      toast.error("تعذر حذف الخطة. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  const [suspensionModalOpen, setSuspensionModalOpen] = useState(false);
  const [itemToSuspend, setItemToSuspend] = useState(null);
  const [resumeConfirmOpen, setResumeConfirmOpen] = useState(false);
  const [itemToResume, setItemToResume] = useState(null);

  function handleSuspend(plan) {
    setItemToSuspend(plan);
    setSuspensionModalOpen(true);
  }

  function closeSuspensionModal() {
    if (isSuspending) return;
    setSuspensionModalOpen(false);
    setItemToSuspend(null);
  }

  async function confirmSuspend(body) {
    if (!itemToSuspend) return false;

    try {
      await suspendPlan({ id: itemToSuspend.id, body }).unwrap();
      toast.success("تم إيقاف الفعالية مؤقتاً بنجاح!");
      setSuspensionModalOpen(false);
      setItemToSuspend(null);
      return true;
    } catch (submitError) {
      toast.error(submitError?.data?.message || "تعذر إيقاف الفعالية مؤقتاً. حاول مرة أخرى.");
      return false;
    }
  }

  function handleResume(plan) {
    const suspensionId = getSubscriptionPlanSuspensionId(plan);
    if (!suspensionId) {
      toast.error("تعذر العثور على معرّف سجل الإيقاف لهذه الفعالية.");
      return;
    }

    setItemToResume({ ...plan, suspensionId });
    setResumeConfirmOpen(true);
  }

  function closeResumeConfirm() {
    if (isResuming) return;
    setResumeConfirmOpen(false);
    setItemToResume(null);
  }

  async function confirmResume() {
    if (!itemToResume) return;

    try {
      await resumePlan({
        id: itemToResume.id,
        suspensionId: itemToResume.suspensionId,
      }).unwrap();
      toast.success("تم استئناف الفعالية وإنهاء الإيقاف بنجاح!");
    } catch (submitError) {
      toast.error(submitError?.data?.message || "تعذر استئناف الفعالية. حاول مرة أخرى.");
    } finally {
      setResumeConfirmOpen(false);
      setItemToResume(null);
    }
  }

  function getEditInitialValues() {
    const plan = detailsPlan || selectedPlan;
    if (!plan) return null;

    const status = getSubscriptionPlanStatus(plan);

    return {
      branch_id: plan.branch_id ? String(plan.branch_id) : "",
      name: planName(plan) === "-" ? "" : planName(plan),
      sessions_per_week: plan.sessions_per_week ? String(plan.sessions_per_week) : "",
      session_count: plan.session_count ? String(plan.session_count) : "",
      price: String(parseAmount(plan.base_price || "")),
      max_subscribers: String(plan.max_subscribers ?? "0"),
      is_active: status === SUBSCRIPTION_PLAN_STATUS.ACTIVE,
      status,
      gender_restriction: plan.gender_restriction || "mixed",
      is_unlimited_subscribers: !!plan.is_unlimited_subscribers,
      activities:
        plan.activities?.map((a) => ({
          activity_id: String(a.activity_id),
          coach_id: a.coach_id ? String(a.coach_id) : "",
        })) || [],
      session_templates:
        plan.session_templates?.map((s) => ({
          day_of_week: String(s.day_of_week),
          start_time: s.start_time || "",
          end_time: s.end_time || "",
        })) || [],
      reason: "",
    };
  }

  return {
    search,
    setSearch,
    statusFilter,
    setStatusFilter,
    drawerMode,
    setDrawerMode,
    selectedPlanId,
    setSelectedPlanId,
    formError,
    setFormError,
    isLoading: isLoading || (isFetching && !listResponse),
    error,
    refetch,
    filteredPlans,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
    stats,
    selectedPlan,
    detailsPlan,
    isFetchingDetails,
    isLoadingDetails,
    detailsError,
    playersData,
    playersError,
    isFetchingPlayers,
    isLoadingPlayers,
    refetchPlayers,
    isCreating,
    isUpdating,
    isDeleting,
    isSuspending,
    isResuming,
    handleCreate,
    handleUpdate,
    handleDelete,
    closeDrawer,
    getEditInitialValues,
    deleteConfirmOpen,
    itemToDelete,
    deleteConfirmation,
    setDeleteConfirmation,
    closeDeleteConfirm,
    confirmDelete,
    suspensionModalOpen,
    itemToSuspend,
    handleSuspend,
    closeSuspensionModal,
    confirmSuspend,
    resumeConfirmOpen,
    itemToResume,
    handleResume,
    closeResumeConfirm,
    confirmResume,
    branches,
    activities,
    coaches,
  };
}

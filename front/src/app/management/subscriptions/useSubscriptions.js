import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import {
  useGetPlayerSubscriptionQuery,
  useGetPlayerSubscriptionsQuery,
  useFreezeSubscriptionMutation,
  useUnfreezeSubscriptionMutation,
  useCancelSubscriptionMutation,
  useRenewSubscriptionMutation,
  useDeletePlayerSubscriptionMutation,
} from "@/lib/api/playerSubscriptionsApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetActivityTypesQuery } from "@/lib/api/activitiesApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useToast } from "@/components/ui/Toast";
import { getBranchesArray } from "@/lib/utils";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  formatSubscriptionMoney,
  getSubscriptionDetail,
  getSubscriptionRows,
  getSubscriptionStats,
  sortSubscriptionsNewestFirst,
} from "./subscriptionUtils";
import { getPaginationMeta, useServerPagination, withAllItems } from "@/lib/pagination";
import { getApiErrorMessage } from "@/lib/apiError";

const VALID_STATUSES = new Set([
  "all",
  "active",
  "expiring_soon",
  "finished",
  "frozen",
  "terminated",
]);
const VALID_PERIODS = new Set(["all", "today", "monthly"]);

function getCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  return Array.isArray(response) ? response : [];
}

/**
 * Coordinates subscription data, filters, selection, and lifecycle mutations.
 */
export function useSubscriptions({ initialData } = {}) {
  const toast = useToast();
  const searchParams = useSearchParams();
  const urlStatus = searchParams?.get("status");
  const initialStatus = urlStatus === "expiring" ? "expiring_soon" : urlStatus;
  const urlPeriod = searchParams?.get("period");
  const urlActivityTypeId = searchParams?.get("activity_type_id");

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState(() => searchParams?.get("search") || "");
  const [debouncedSearch, setDebouncedSearch] = useState(search.trim());
  const [status, setStatus] = useState(() =>
    VALID_STATUSES.has(initialStatus) ? initialStatus : "all",
  );
  const [period, setPeriod] = useState(() => (VALID_PERIODS.has(urlPeriod) ? urlPeriod : "all"));
  const [activityTypeId, setActivityTypeId] = useState(() => urlActivityTypeId || "all");
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState(null);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [isRefunded, setIsRefunded] = useState(false);
  const [deleteReason, setDeleteReason] = useState("");
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const [renewalSubscription, setRenewalSubscription] = useState(null);
  const [renewalErrorMessage, setRenewalErrorMessage] = useState("");
  useEffect(() => {
    const timeoutId = window.setTimeout(() => setDebouncedSearch(search.trim()), 300);
    return () => window.clearTimeout(timeoutId);
  }, [search]);

  const paginationFilterKey = [branchFilter, status, period, activityTypeId, debouncedSearch].join(
    "|",
  );
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);

  const queryParams = useMemo(() => {
    return {
      ...(branchFilter !== "all" ? { branch_id: branchFilter } : {}),
      ...(activityTypeId !== "all" ? { activity_type_id: activityTypeId } : {}),
      ...(debouncedSearch ? { search: debouncedSearch } : {}),
      status,
      period,
      page,
      per_page: perPage,
    };
  }, [activityTypeId, branchFilter, debouncedSearch, page, perPage, period, status]);

  const {
    currentData: data,
    error,
    isFetching,
    isLoading,
    refetch,
  } = useGetPlayerSubscriptionsQuery(queryParams);

  const {
    data: subscriptionDetailData,
    error: subscriptionDetailError,
    isFetching: isSubscriptionDetailFetching,
    isLoading: isSubscriptionDetailLoading,
    refetch: refetchSubscriptionDetail,
  } = useGetPlayerSubscriptionQuery(selectedSubscriptionId, {
    skip: !selectedSubscriptionId,
  });

  const { data: branchesData } = useGetBranchesQuery(withAllItems());
  const {
    data: activityTypesData,
    isLoading: isActivityTypesLoading,
    isFetching: isActivityTypesFetching,
  } = useGetActivityTypesQuery(withAllItems());
  const renewalBranchId =
    renewalSubscription?.branch_id ??
    renewalSubscription?.branch?.id ??
    renewalSubscription?.plan?.branch_id ??
    renewalSubscription?.plan?.branch?.id;
  const {
    data: renewalPlansData,
    error: renewalPlansError,
    isLoading: isRenewalPlansLoading,
    isFetching: isRenewalPlansFetching,
  } = useGetSubscriptionPlansQuery(
    {
      available: true,
      per_page: "all",
      ...(renewalBranchId ? { branch_id: renewalBranchId } : {}),
    },
    { skip: !renewalSubscription },
  );
  const [freezeSubscription, { isLoading: isFreezing }] = useFreezeSubscriptionMutation();
  const [unfreezeSubscription, { isLoading: isUnfreezing }] = useUnfreezeSubscriptionMutation();
  const [cancelSubscription, { isLoading: isCancelling }] = useCancelSubscriptionMutation();
  const [renewSubscription, { isLoading: isRenewing }] = useRenewSubscriptionMutation();
  const [deletePlayerSubscription, { isLoading: isDeleting }] =
    useDeletePlayerSubscriptionMutation();

  const canUseInitialSubscriptions =
    !debouncedSearch &&
    status === "all" &&
    period === "all" &&
    activityTypeId === "all" &&
    page === 1 &&
    perPage === 15 &&
    branchFilter === "all";
  const listResponse = data || (canUseInitialSubscriptions ? initialData?.subscriptions : null);
  const subscriptions = useMemo(
    () => sortSubscriptionsNewestFirst(getSubscriptionRows(listResponse)),
    [listResponse],
  );
  const pagination = useMemo(
    () => getPaginationMeta(listResponse, { page, perPage }),
    [listResponse, page, perPage],
  );
  const branches = useMemo(
    () => getBranchesArray(branchesData || initialData?.branches),
    [branchesData, initialData?.branches],
  );
  const activityTypes = useMemo(
    () => getCollection(activityTypesData || initialData?.activityTypes),
    [activityTypesData, initialData?.activityTypes],
  );
  const renewalPlans = useMemo(() => getCollection(renewalPlansData), [renewalPlansData]);
  const selectedSubscription = useMemo(
    () => getSubscriptionDetail(subscriptionDetailData),
    [subscriptionDetailData],
  );

  const totalResults = pagination.total;

  const stats = useMemo(() => {
    const subscriptionStats = getSubscriptionStats(listResponse, subscriptions);

    return [
      {
        title: "إجمالي الاشتراكات",
        value: subscriptionStats.totalSubscriptions.toLocaleString("ar"),
        helper: "إجمالي الاشتراكات المسجلة",
        tone: "yellow",
        compact: true,
        onClick: () => setStatus("all"),
        active: status === "all",
      },
      {
        title: "الاشتراكات النشطة",
        value: subscriptionStats.activeSubscriptions.toLocaleString("ar"),
        helper: "الاشتراكات الفعالة حالياً",
        tone: "green",
        compact: true,
        onClick: () => setStatus(status === "active" ? "all" : "active"),
        active: status === "active",
      },
      {
        title: "المبالغ المدفوعة",
        value: formatSubscriptionMoney(subscriptionStats.totalPaidAmount),
        helper: "إجمالي المبالغ المحصلة",
        tone: "blue",
        compact: true,
      },
      {
        title: "إيرادات اليوم",
        value: formatSubscriptionMoney(subscriptionStats.todayRevenue),
        helper: "المبالغ المحصلة اليوم",
        tone: "purple",
        compact: true,
        onClick: () => setPeriod(period === "today" ? "all" : "today"),
        active: period === "today",
      },
    ];
  }, [listResponse, period, status, subscriptions]);

  const errorMessage =
    error?.data?.message ||
    (error?.status || error?.error ? `رمز الخطأ: ${error?.status || error?.error}` : "");

  function closeDrawer() {
    setSelectedSubscriptionId(null);
  }

  async function handleFreeze(id, body) {
    try {
      await freezeSubscription({ id, body }).unwrap();
      toast.success("تم تجميد الاشتراك بنجاح!");
    } catch (err) {
      const errMsg =
        err?.data?.message ||
        (err?.data?.errors && Object.values(err.data.errors).flat()[0]) ||
        "تعذر تجميد الاشتراك. حاول مرة أخرى.";
      toast.error(errMsg);
    }
  }

  async function handleUnfreeze(id) {
    try {
      await unfreezeSubscription(id).unwrap();
      toast.success("تم إلغاء تجميد الاشتراك وتفعيله بنجاح!");
    } catch (err) {
      const errMsg = err?.data?.message || "تعذر إلغاء تجميد الاشتراك. حاول مرة أخرى.";
      toast.error(errMsg);
    }
  }

  async function handleCancel(id) {
    if (
      !window.confirm(
        "هل أنت متأكد من رغبتك في إلغاء هذا الاشتراك؟ لا يمكن التراجع عن هذا الإجراء.",
      )
    ) {
      return;
    }
    try {
      await cancelSubscription(id).unwrap();
      toast.success("تم إلغاء الاشتراك بنجاح!");
    } catch {
      toast.error("تعذر إلغاء الاشتراك. حاول مرة أخرى.");
    }
  }

  function openRenewal(subscription) {
    setRenewalErrorMessage("");
    setRenewalSubscription(subscription);
  }

  function closeRenewal() {
    if (isRenewing) return;
    setRenewalSubscription(null);
    setRenewalErrorMessage("");
  }

  async function handleRenew(values) {
    if (!renewalSubscription?.id) return false;
    setRenewalErrorMessage("");

    try {
      await renewSubscription({ id: renewalSubscription.id, body: values }).unwrap();
      toast.success("تم تجديد الاشتراك بنجاح!");
      setRenewalSubscription(null);
      return true;
    } catch (submitError) {
      setRenewalErrorMessage(
        getApiErrorMessage(
          submitError,
          "تعذر تجديد الاشتراك. تحقق من بيانات الدفع وحاول مرة أخرى.",
        ),
      );
      return false;
    }
  }

  function handleDelete(subscription) {
    setItemToDelete(subscription);
    setIsRefunded(false);
    setDeleteReason("");
    setDeleteConfirmation("");
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
    setIsRefunded(false);
    setDeleteReason("");
    setDeleteConfirmation("");
  }

  async function confirmDelete() {
    if (!itemToDelete || deleteConfirmation !== "delete") return;
    try {
      await deletePlayerSubscription({
        id: itemToDelete.id,
        confirmation: deleteConfirmation,
        is_refunded: isRefunded,
        reason: deleteReason ? deleteReason.trim() : undefined,
      }).unwrap();
      toast.success(
        isRefunded ? "تم حذف الاشتراك واسترداد المبلغ بنجاح!" : "تم حذف الاشتراك بنجاح!",
      );
    } catch {
      toast.error("تعذر حذف الاشتراك. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  return {
    search,
    setSearch,
    status,
    setStatus,
    period,
    setPeriod,
    activityTypeId,
    setActivityTypeId,
    branchFilter,
    setBranchFilter,
    selectedSubscriptionId,
    setSelectedSubscriptionId,
    error,
    isFetching,
    isLoading: isLoading || (isFetching && !listResponse),
    refetch,
    subscriptionDetailError,
    isSubscriptionDetailFetching,
    isSubscriptionDetailLoading,
    refetchSubscriptionDetail,
    subscriptions,
    selectedSubscription,
    filteredSubscriptions: subscriptions,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
    stats,
    errorMessage,
    branches,
    activityTypes,
    isActivityTypesLoading: isActivityTypesLoading || isActivityTypesFetching,
    isFreezing,
    isUnfreezing,
    isCancelling,
    isRenewing,
    isDeleting,
    renewalSubscription,
    renewalPlans,
    isRenewalPlansLoading: isRenewalPlansLoading || isRenewalPlansFetching,
    renewalPlansErrorMessage: renewalPlansError
      ? getApiErrorMessage(renewalPlansError, "تعذر تحميل خطط الاشتراك المتاحة.")
      : "",
    renewalErrorMessage,
    deleteConfirmOpen,
    itemToDelete,
    isRefunded,
    setIsRefunded,
    deleteReason,
    setDeleteReason,
    deleteConfirmation,
    setDeleteConfirmation,
    handleFreeze,
    handleUnfreeze,
    handleCancel,
    openRenewal,
    closeRenewal,
    handleRenew,
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    closeDrawer,
  };
}

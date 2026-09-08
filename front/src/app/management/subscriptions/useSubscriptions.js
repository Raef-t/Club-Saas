import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import {
  useGetPlayerSubscriptionQuery,
  useGetPlayerSubscriptionsQuery,
  useFreezeSubscriptionMutation,
  useUnfreezeSubscriptionMutation,
  useCancelSubscriptionMutation,
  useDeletePlayerSubscriptionMutation,
} from "@/lib/api/playerSubscriptionsApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
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

const VALID_STATUSES = new Set([
  "all",
  "active",
  "expiring_soon",
  "finished",
  "frozen",
  "terminated",
]);
const VALID_PERIODS = new Set(["all", "today", "monthly"]);

/**
 * Coordinates subscription data, filters, selection, and lifecycle mutations.
 */
export function useSubscriptions({ initialData } = {}) {
  const toast = useToast();
  const searchParams = useSearchParams();
  const urlStatus = searchParams?.get("status");
  const initialStatus = urlStatus === "expiring" ? "expiring_soon" : urlStatus;
  const urlPeriod = searchParams?.get("period");

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState(() => searchParams?.get("search") || "");
  const [debouncedSearch, setDebouncedSearch] = useState(search.trim());
  const [status, setStatus] = useState(() =>
    VALID_STATUSES.has(initialStatus) ? initialStatus : "all",
  );
  const [period, setPeriod] = useState(() => (VALID_PERIODS.has(urlPeriod) ? urlPeriod : "all"));
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState(null);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [isRefunded, setIsRefunded] = useState(false);
  const [deleteReason, setDeleteReason] = useState("");
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  useEffect(() => {
    const timeoutId = window.setTimeout(() => setDebouncedSearch(search.trim()), 300);
    return () => window.clearTimeout(timeoutId);
  }, [search]);

  const paginationFilterKey = [branchFilter, status, period, debouncedSearch].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);

  const queryParams = useMemo(() => {
    return {
      ...(branchFilter !== "all" ? { branch_id: branchFilter } : {}),
      ...(debouncedSearch ? { search: debouncedSearch } : {}),
      status,
      period,
      page,
      per_page: perPage,
    };
  }, [branchFilter, debouncedSearch, page, perPage, period, status]);

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
  const [freezeSubscription, { isLoading: isFreezing }] = useFreezeSubscriptionMutation();
  const [unfreezeSubscription, { isLoading: isUnfreezing }] = useUnfreezeSubscriptionMutation();
  const [cancelSubscription, { isLoading: isCancelling }] = useCancelSubscriptionMutation();
  const [deletePlayerSubscription, { isLoading: isDeleting }] =
    useDeletePlayerSubscriptionMutation();

  const canUseInitialSubscriptions =
    !debouncedSearch &&
    status === "all" &&
    period === "all" &&
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
    isFreezing,
    isUnfreezing,
    isCancelling,
    isDeleting,
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
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    closeDrawer,
  };
}

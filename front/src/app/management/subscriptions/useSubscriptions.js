import { useMemo, useState } from "react";
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
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import {
  formatSubscriptionMoney,
  getSubscriptionDetail,
  getSubscriptionRows,
  parseSubscriptionAmount,
} from "./subscriptionUtils";

function isExpiringSoon(subscription) {
  if (subscription.status !== "active") return false;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const endDate = new Date(subscription.end_date);
  if (Number.isNaN(endDate.getTime())) return false;
  endDate.setHours(23, 59, 59, 999);
  const diffDays = (endDate - today) / (1000 * 60 * 60 * 24);
  return diffDays >= 0 && diffDays <= 7;
}

/**
 * Coordinates subscription data, filters, selection, and lifecycle mutations.
 */
export function useSubscriptions({ initialData } = {}) {
  const toast = useToast();
  const searchParams = useSearchParams();
  const urlStatus = searchParams?.get("status");
  const initialStatus =
    urlStatus === "expiring_soon" || urlStatus === "expiring"
      ? "expiring_soon"
      : urlStatus || "all";

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState(initialStatus);
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState(null);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [isRefunded, setIsRefunded] = useState(false);
  const [deleteReason, setDeleteReason] = useState("");
  const [deleteConfirmation, setDeleteConfirmation] = useState("");

  const queryParams = useMemo(() => {
    return branchFilter !== "all" ? { branch_id: branchFilter } : {};
  }, [branchFilter]);

  const { data, error, isFetching, isLoading, refetch } =
    useGetPlayerSubscriptionsQuery(queryParams);

  const {
    data: subscriptionDetailData,
    error: subscriptionDetailError,
    isFetching: isSubscriptionDetailFetching,
    isLoading: isSubscriptionDetailLoading,
    refetch: refetchSubscriptionDetail,
  } = useGetPlayerSubscriptionQuery(selectedSubscriptionId, {
    skip: !selectedSubscriptionId,
  });

  const { data: branchesData } = useGetBranchesQuery();
  const [freezeSubscription, { isLoading: isFreezing }] = useFreezeSubscriptionMutation();
  const [unfreezeSubscription, { isLoading: isUnfreezing }] = useUnfreezeSubscriptionMutation();
  const [cancelSubscription, { isLoading: isCancelling }] = useCancelSubscriptionMutation();
  const [deletePlayerSubscription, { isLoading: isDeleting }] =
    useDeletePlayerSubscriptionMutation();

  const subscriptions = useMemo(
    () => getSubscriptionRows(data || initialData?.subscriptions),
    [data, initialData?.subscriptions],
  );
  const branchSubscriptions = useMemo(
    () => filterEntitiesByBranch(subscriptions, branchFilter),
    [branchFilter, subscriptions],
  );
  const branches = useMemo(
    () => getBranchesArray(branchesData || initialData?.branches),
    [branchesData, initialData?.branches],
  );
  const selectedSubscription = useMemo(
    () => getSubscriptionDetail(subscriptionDetailData),
    [subscriptionDetailData],
  );

  const filteredSubscriptions = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return branchSubscriptions.filter((subscription) => {
      const member = subscription.member || {};
      const person = member.person || {};
      const plan = subscription.plan || {};
      const planName =
        typeof plan.name === "string" ? plan.name : plan.name?.ar || plan.name?.en || "";
      const matchesStatus =
        status === "all" ||
        (status === "expiring_soon" || status === "expiring"
          ? isExpiringSoon(subscription)
          : subscription.status === status);
      const matchesSearch =
        !normalizedSearch ||
        [person.full_name, person.phone, member.member_number, planName]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(normalizedSearch));

      return matchesStatus && matchesSearch;
    });
  }, [branchSubscriptions, search, status]);

  const stats = useMemo(() => {
    const activeCount = branchSubscriptions.filter((item) => item.status === "active").length;
    const totalPaid = branchSubscriptions.reduce(
      (sum, item) => sum + parseSubscriptionAmount(item.paid_amount),
      0,
    );
    const totalRemaining = branchSubscriptions.reduce(
      (sum, item) => sum + parseSubscriptionAmount(item.remaining_amount),
      0,
    );
    const soon = branchSubscriptions.filter(isExpiringSoon).length;

    return [
      {
        title: "إجمالي الاشتراكات",
        value: branchSubscriptions.length.toLocaleString("ar"),
        helper: "كل الاشتراكات المسترجعة",
        tone: "yellow",
        compact: true,
        onClick: () => setStatus("all"),
        active: status === "all",
      },
      {
        title: "الاشتراكات النشطة",
        value: activeCount.toLocaleString("ar"),
        helper: "حالة العضوية active",
        tone: "green",
        compact: true,
        onClick: () => setStatus(status === "active" ? "all" : "active"),
        active: status === "active",
      },
      {
        title: "المبالغ المدفوعة",
        value: formatSubscriptionMoney(totalPaid),
        tone: "blue",
        compact: true,
      },
      {
        title: "المتبقي للتحصيل",
        value: formatSubscriptionMoney(totalRemaining),
        helper: `${soon.toLocaleString("ar")} اشتراك ينتهي خلال ٧ أيام`,
        tone: "purple",
        compact: true,
        onClick: () =>
          setStatus(status === "expiring_soon" || status === "expiring" ? "all" : "expiring_soon"),
        active: status === "expiring_soon" || status === "expiring",
      },
    ];
  }, [branchSubscriptions, status]);

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
    } catch {
      toast.error("تعذر تجميد الاشتراك. حاول مرة أخرى.");
    }
  }

  async function handleUnfreeze(id) {
    try {
      await unfreezeSubscription(id).unwrap();
      toast.success("تم إلغاء تجميد الاشتراك وتفعيله بنجاح!");
    } catch {
      toast.error("تعذر إلغاء تجميد الاشتراك. حاول مرة أخرى.");
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
        is_refunded: isRefunded,
        reason: deleteReason ? deleteReason.trim() : undefined,
      }).unwrap();
      toast.success(
        isRefunded
          ? "تم حذف الاشتراك واسترداد المبلغ بنجاح!"
          : "تم حذف الاشتراك بنجاح!"
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
    branchFilter,
    setBranchFilter,
    selectedSubscriptionId,
    setSelectedSubscriptionId,
    error,
    isFetching,
    isLoading,
    refetch,
    subscriptionDetailError,
    isSubscriptionDetailFetching,
    isSubscriptionDetailLoading,
    refetchSubscriptionDetail,
    subscriptions,
    selectedSubscription,
    filteredSubscriptions,
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

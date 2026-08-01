import { useMemo, useState } from "react";
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

/**
 * Coordinates subscription data, filters, selection, and lifecycle mutations.
 */
export function useSubscriptions({ initialData } = {}) {
  const toast = useToast();
  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState(null);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

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
  const [deletePlayerSubscription, { isLoading: isDeleting }] = useDeletePlayerSubscriptionMutation();

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
      const planName = plan.name?.ar || plan.name?.en || "";
      const matchesStatus = status === "all" || subscription.status === status;
      const matchesSearch =
        !normalizedSearch ||
        [person.full_name, person.email, person.phone, member.member_number, planName]
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
    const today = new Date();
    const soon = branchSubscriptions.filter((item) => {
      const endDate = new Date(item.end_date);
      if (Number.isNaN(endDate.getTime())) return false;
      const diffDays = (endDate - today) / (1000 * 60 * 60 * 24);
      return diffDays >= 0 && diffDays <= 7;
    }).length;

    return [
      {
        title: "إجمالي الاشتراكات",
        value: branchSubscriptions.length.toLocaleString("ar"),
        helper: "كل الاشتراكات المسترجعة",
        tone: "yellow",
        compact: true,
      },
      {
        title: "الاشتراكات النشطة",
        value: activeCount.toLocaleString("ar"),
        helper: "حالة العضوية active",
        tone: "green",
        compact: true,
      },
      {
        title: "المبالغ المدفوعة",
        value: formatSubscriptionMoney(totalPaid),
        helper: "حسب paid_amount",
        tone: "blue",
        compact: true,
      },
      {
        title: "المتبقي للتحصيل",
        value: totalRemaining ? formatSubscriptionMoney(totalRemaining) : "$٠",
        helper: `${soon.toLocaleString("ar")} اشتراك ينتهي خلال ٧ أيام`,
        tone: "purple",
        compact: true,
      },
    ];
  }, [branchSubscriptions]);

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
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deletePlayerSubscription(itemToDelete.id).unwrap();
      toast.success("تم حذف الاشتراك بنجاح!");
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
    handleFreeze,
    handleUnfreeze,
    handleCancel,
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    closeDrawer,
  };
}

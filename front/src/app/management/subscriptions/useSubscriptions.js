import { useMemo, useState } from "react";
import {
  useGetPlayerSubscriptionQuery,
  useGetPlayerSubscriptionsQuery,
  useCreatePlayerSubscriptionMutation,
  useFreezeSubscriptionMutation,
  useUnfreezeSubscriptionMutation,
  useCancelSubscriptionMutation,
} from "@/lib/api/playerSubscriptionsApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useToast } from "@/components/ui/Toast";
import { formatMoney as baseFormatMoney } from "@/lib/utils";

function parseAmount(value) {
  const number = Number.parseFloat(value || 0);
  return Number.isFinite(number) ? number : 0;
}

function formatMoney(value) {
  return baseFormatMoney(value, "$");
}

function getSubscriptionRows(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  return [];
}

function getSubscriptionDetail(response) {
  return response?.data || null;
}

export function useSubscriptions() {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [branchFilter, setBranchFilter] = useState("all");
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState(null);
  const [drawerMode, setDrawerMode] = useState(null);
  const [formError, setFormError] = useState("");

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
  const { data: membersData } = useGetMembersQuery();
  const { data: plansData } = useGetSubscriptionPlansQuery();
  const { data: activitiesData } = useGetActivitiesQuery();
  const { data: coachesData } = useGetCoachesQuery();

  const [createPlayerSubscription, { isLoading: isCreating }] =
    useCreatePlayerSubscriptionMutation();
  const [freezeSubscription, { isLoading: isFreezing }] =
    useFreezeSubscriptionMutation();
  const [unfreezeSubscription, { isLoading: isUnfreezing }] =
    useUnfreezeSubscriptionMutation();
  const [cancelSubscription, { isLoading: isCancelling }] =
    useCancelSubscriptionMutation();

  const subscriptions = useMemo(() => getSubscriptionRows(data), [data]);
  const branches = useMemo(() => branchesData?.data || [], [branchesData]);
  const members = useMemo(() => membersData?.data || [], [membersData]);
  const plans = useMemo(() => plansData?.data || [], [plansData]);
  const activities = useMemo(() => activitiesData?.data || [], [activitiesData]);
  const coaches = useMemo(() => coachesData?.data || [], [coachesData]);

  const selectedSubscription = useMemo(
    () => getSubscriptionDetail(subscriptionDetailData),
    [subscriptionDetailData],
  );

  const filteredSubscriptions = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return subscriptions.filter((subscription) => {
      const member = subscription.member || {};
      const person = member.person || {};
      const plan = subscription.plan || {};
      const planName = plan.name?.ar || plan.name?.en || "";
      const matchesStatus = status === "all" || subscription.status === status;
      const matchesSearch =
        !normalizedSearch ||
        [
          person.full_name,
          person.email,
          person.phone,
          member.member_number,
          planName,
        ]
          .filter(Boolean)
          .some((value) =>
            String(value).toLowerCase().includes(normalizedSearch),
          );

      return matchesStatus && matchesSearch;
    });
  }, [search, status, subscriptions]);

  const stats = useMemo(() => {
    const activeCount = subscriptions.filter(
      (item) => item.status === "active",
    ).length;
    const totalPaid = subscriptions.reduce(
      (sum, item) => sum + parseAmount(item.paid_amount),
      0,
    );
    const totalRemaining = subscriptions.reduce(
      (sum, item) => sum + parseAmount(item.remaining_amount),
      0,
    );
    const today = new Date();
    const soon = subscriptions.filter((item) => {
      const endDate = new Date(item.end_date);
      if (Number.isNaN(endDate.getTime())) return false;
      const diffDays = (endDate - today) / (1000 * 60 * 60 * 24);
      return diffDays >= 0 && diffDays <= 7;
    }).length;

    return [
      {
        title: "إجمالي الاشتراكات",
        value: subscriptions.length.toLocaleString("ar"),
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
        value: formatMoney(totalPaid),
        helper: "حسب paid_amount",
        tone: "blue",
        compact: true,
      },
      {
        title: "المتبقي للتحصيل",
        value: totalRemaining ? formatMoney(totalRemaining) : "$٠",
        helper: `${soon.toLocaleString("ar")} اشتراك ينتهي خلال ٧ أيام`,
        tone: "purple",
        compact: true,
      },
    ];
  }, [subscriptions]);

  const errorMessage =
    error?.data?.message ||
    (error?.status || error?.error
      ? `رمز الخطأ: ${error?.status || error?.error}`
      : "");

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedSubscriptionId(null);
    setFormError("");
  }

  async function handleCreateSubscription(values) {
    setFormError("");
    try {
      await createPlayerSubscription(values).unwrap();
      toast.success("تم تسجيل الاشتراك الجديد بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message ||
          "تعذر إنشاء الاشتراك. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
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
    if (!window.confirm("هل أنت متأكد من رغبتك في إلغاء هذا الاشتراك؟ لا يمكن التراجع عن هذا الإجراء.")) {
      return;
    }
    try {
      await cancelSubscription(id).unwrap();
      toast.success("تم إلغاء الاشتراك بنجاح!");
    } catch {
      toast.error("تعذر إلغاء الاشتراك. حاول مرة أخرى.");
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
    drawerMode,
    setDrawerMode,
    formError,
    setFormError,
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
    members,
    plans,
    activities,
    coaches,
    isCreating,
    isFreezing,
    isUnfreezing,
    isCancelling,
    handleCreateSubscription,
    handleFreeze,
    handleUnfreeze,
    handleCancel,
    closeDrawer,
  };
}

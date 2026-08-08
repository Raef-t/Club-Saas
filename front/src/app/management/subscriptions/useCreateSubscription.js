import { useMemo, useState } from "react";
import {
  useCreatePlayerSubscriptionMutation,
  useGetPlayerSubscriptionQuery,
  useUpdatePlayerSubscriptionMutation,
} from "@/lib/api/playerSubscriptionsApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useGetActivitiesQuery } from "@/lib/api/activitiesApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { getSubscriptionDetail } from "./subscriptionUtils";

/**
 * Returns an array from the standard collection response used by the backend.
 */
function getCollection(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

/**
 * Coordinates the reference data and mutation used by the create-subscription page.
 */
export function useCreateSubscription({ initialData, selectedSubscriptionId = null } = {}) {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const [formError, setFormError] = useState("");
  const branchQueryParams = selectedBranchId === "all" ? {} : { branch_id: selectedBranchId };
  const { data: membersData } = useGetMembersQuery(branchQueryParams);
  const { data: plansData } = useGetSubscriptionPlansQuery(branchQueryParams);
  const { data: activitiesData } = useGetActivitiesQuery(branchQueryParams);
  const { data: coachesData } = useGetCoachesQuery(branchQueryParams);
  const {
    data: subscriptionDetailResponse,
    error: subscriptionDetailError,
    isLoading: isSubscriptionDetailLoading,
    isFetching: isSubscriptionDetailFetching,
    refetch: refetchSubscriptionDetail,
  } = useGetPlayerSubscriptionQuery(selectedSubscriptionId, {
    skip: !selectedSubscriptionId,
  });
  const [createPlayerSubscription, { isLoading: isCreating }] =
    useCreatePlayerSubscriptionMutation();
  const [updatePlayerSubscription, { isLoading: isUpdating }] =
    useUpdatePlayerSubscriptionMutation();

  const allMembers = useMemo(
    () => getCollection(membersData || initialData?.members),
    [initialData?.members, membersData],
  );
  const members = useMemo(
    () => filterEntitiesByBranch(allMembers, selectedBranchId),
    [allMembers, selectedBranchId],
  );
  const allPlans = useMemo(
    () => getCollection(plansData || initialData?.plans),
    [initialData?.plans, plansData],
  );
  const plans = useMemo(
    () => filterEntitiesByBranch(allPlans, selectedBranchId),
    [allPlans, selectedBranchId],
  );
  const allActivities = useMemo(
    () => getCollection(activitiesData || initialData?.activities),
    [activitiesData, initialData?.activities],
  );
  const activities = useMemo(
    () => filterEntitiesByBranch(allActivities, selectedBranchId),
    [allActivities, selectedBranchId],
  );
  const allCoaches = useMemo(
    () => getCollection(coachesData || initialData?.coaches),
    [coachesData, initialData?.coaches],
  );
  const coaches = useMemo(
    () => filterEntitiesByBranch(allCoaches, selectedBranchId),
    [allCoaches, selectedBranchId],
  );
  const selectedSubscription = useMemo(
    () => getSubscriptionDetail(subscriptionDetailResponse),
    [subscriptionDetailResponse],
  );

  /**
   * Creates the subscription and reports validation or backend errors to the form.
   */
  async function handleCreateSubscription(values) {
    setFormError("");

    try {
      await createPlayerSubscription(values).unwrap();
      toast.success("تم تسجيل الاشتراك الجديد بنجاح!");
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر إنشاء الاشتراك. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  async function handleUpdateSubscription(values) {
    if (!selectedSubscriptionId) return false;
    setFormError("");

    try {
      await updatePlayerSubscription({ id: selectedSubscriptionId, body: values }).unwrap();
      toast.success("تم تعديل الاشتراك بنجاح!");
      return true;
    } catch (submitError) {
      setFormError(
        submitError?.data?.message || "تعذر تعديل الاشتراك. تحقق من البيانات وحاول مرة أخرى.",
      );
      return false;
    }
  }

  return {
    members,
    plans,
    activities,
    coaches,
    selectedSubscription,
    subscriptionDetailError,
    isSubscriptionDetailLoading,
    isSubscriptionDetailFetching,
    refetchSubscriptionDetail,
    formError,
    isCreating,
    isUpdating,
    handleCreateSubscription,
    handleUpdateSubscription,
  };
}

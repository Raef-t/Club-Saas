import { useEffect, useMemo, useState } from "react";
import {
  useCreatePlayerSubscriptionMutation,
  useGetPlayerSubscriptionQuery,
  useUpdatePlayerSubscriptionMutation,
} from "@/lib/api/playerSubscriptionsApi";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useGetActivitiesQuery, useGetActivityTypesQuery } from "@/lib/api/activitiesApi";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { withAllItems } from "@/lib/pagination";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { getApiErrorMessage } from "@/lib/apiError";
import {
  getAvailableSubscriptionPlanParams,
  getDefaultSubscriptionActivityTypeId,
  getSubscriptionActivityTypeId,
  getSubscriptionDetail,
} from "./subscriptionUtils";

/**
 * Returns an array from the standard collection response used by the backend.
 */
function getCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return Array.isArray(response?.data) ? response.data : [];
}

/**
 * Coordinates the reference data and mutation used by the create-subscription page.
 */
export function useCreateSubscription({ initialData, selectedSubscriptionId = null } = {}) {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const [formError, setFormError] = useState("");
  const [selectedActivityTypeId, setSelectedActivityTypeId] = useState(() =>
    getDefaultSubscriptionActivityTypeId(getCollection(initialData?.activityTypes)),
  );
  const branchQueryParams = selectedBranchId === "all" ? {} : { branch_id: selectedBranchId };
  const planQueryParams = useMemo(
    () => getAvailableSubscriptionPlanParams(selectedBranchId, selectedActivityTypeId),
    [selectedActivityTypeId, selectedBranchId],
  );
  const { data: membersData } = useGetMembersQuery(withAllItems(branchQueryParams));
  const {
    currentData: plansData,
    error: plansError,
    isLoading: isPlansLoading,
    isFetching: isPlansFetching,
  } = useGetSubscriptionPlansQuery(planQueryParams, { skip: !selectedActivityTypeId });
  const {
    currentData: activityTypesData,
    error: activityTypesError,
    isLoading: isActivityTypesLoading,
    isFetching: isActivityTypesFetching,
  } = useGetActivityTypesQuery(withAllItems());
  const { data: activitiesData } = useGetActivitiesQuery(withAllItems(branchQueryParams));
  const { data: coachesData } = useGetCoachesQuery(withAllItems(branchQueryParams));
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
  const plansResponse = plansData;
  const allPlans = useMemo(() => getCollection(plansResponse), [plansResponse]);
  const plans = useMemo(
    () => filterEntitiesByBranch(allPlans, selectedBranchId),
    [allPlans, selectedBranchId],
  );
  const activityTypes = useMemo(
    () => getCollection(activityTypesData || initialData?.activityTypes),
    [activityTypesData, initialData?.activityTypes],
  );
  useEffect(() => {
    setSelectedActivityTypeId((currentId) => {
      const currentTypeExists = activityTypes.some(
        (activityType) => String(activityType.id) === String(currentId),
      );
      return currentTypeExists ? currentId : getDefaultSubscriptionActivityTypeId(activityTypes);
    });
  }, [activityTypes]);
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

  useEffect(() => {
    if (!selectedSubscriptionId || !selectedSubscription) return;

    const subscriptionActivityTypeId = getSubscriptionActivityTypeId(selectedSubscription);
    if (subscriptionActivityTypeId) {
      setSelectedActivityTypeId(subscriptionActivityTypeId);
    }
  }, [selectedSubscription, selectedSubscriptionId]);

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
        getApiErrorMessage(submitError, "تعذر إنشاء الاشتراك. تحقق من البيانات وحاول مرة أخرى."),
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
        getApiErrorMessage(submitError, "تعذر تعديل الاشتراك. تحقق من البيانات وحاول مرة أخرى."),
      );
      return false;
    }
  }

  return {
    members,
    plans,
    activityTypes,
    selectedActivityTypeId,
    setSelectedActivityTypeId,
    isPlansLoading:
      isPlansLoading || isPlansFetching || (!selectedActivityTypeId && !activityTypesError),
    plansErrorMessage: plansError
      ? getApiErrorMessage(plansError, "تعذر تحميل باقات الاشتراك المتاحة.")
      : "",
    isActivityTypesLoading: isActivityTypesLoading || isActivityTypesFetching,
    activityTypesErrorMessage: activityTypesError
      ? getApiErrorMessage(activityTypesError, "تعذر تحميل أنواع الأنشطة.")
      : "",
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

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
import { useGetOffersQuery, useSubscribeToOfferMutation } from "@/lib/api/offersApi";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { withAllItems } from "@/lib/pagination";
import { filterEntitiesByBranch, getEntityBranchIds } from "@/lib/managementBranchUtils";
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
export function useCreateSubscription({
  initialData,
  selectedSubscriptionId = null,
  initialActivityTypeId = "",
} = {}) {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const [formError, setFormError] = useState("");
  const [selectedActivityTypeId, setSelectedActivityTypeId] = useState(
    () => initialActivityTypeId || "",
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
  } = useGetSubscriptionPlansQuery(planQueryParams);
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

  const offerQueryParams = useMemo(() => {
    const params = { all: true, available_only: true };
    if (selectedBranchId && selectedBranchId !== "all") {
      params.branch_id = selectedBranchId;
    }
    if (selectedActivityTypeId && selectedActivityTypeId !== "all") {
      params.activity_type_id = selectedActivityTypeId;
    }
    return params;
  }, [selectedBranchId, selectedActivityTypeId]);

  const {
    data: offersData,
    isLoading: isOffersLoading,
    isFetching: isOffersFetching,
  } = useGetOffersQuery(offerQueryParams);

  const [subscribeOffer, { isLoading: isSubscribingOffer }] = useSubscribeToOfferMutation();

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
  const plans = useMemo(() => {
    const hasBranchInfo = allPlans.some((plan) => getEntityBranchIds(plan).length > 0);
    return hasBranchInfo ? filterEntitiesByBranch(allPlans, selectedBranchId) : allPlans;
  }, [allPlans, selectedBranchId]);
  const activityTypes = useMemo(
    () => getCollection(activityTypesData || initialData?.activityTypes),
    [activityTypesData, initialData?.activityTypes],
  );

  const rawOffers = useMemo(
    () => getCollection(offersData || initialData?.offers),
    [offersData, initialData?.offers],
  );

  const offers = useMemo(() => {
    return rawOffers.filter((offer) => {
      if (!offer.is_available) return false;
      if (
        selectedBranchId &&
        selectedBranchId !== "all" &&
        offer.branch_id &&
        String(offer.branch_id) !== String(selectedBranchId)
      ) {
        return false;
      }
      if (selectedActivityTypeId && selectedActivityTypeId !== "all") {
        const offerPlans = offer.plans || [];
        const matchesActivity = offerPlans.some((p) => {
          if (
            Array.isArray(p.activity_types) &&
            p.activity_types.some((at) => String(at.id) === String(selectedActivityTypeId))
          ) {
            return true;
          }
          if (
            Array.isArray(p.activities) &&
            p.activities.some((act) => String(act.activity_type_id) === String(selectedActivityTypeId))
          ) {
            return true;
          }
          const fullPlan = allPlans.find((pl) => String(pl.id) === String(p.id));
          if (fullPlan) {
            if (
              Array.isArray(fullPlan.activity_types) &&
              fullPlan.activity_types.some((at) => String(at.id) === String(selectedActivityTypeId))
            ) {
              return true;
            }
            if (
              Array.isArray(fullPlan.activities) &&
              fullPlan.activities.some((act) => String(act.activity_type_id) === String(selectedActivityTypeId))
            ) {
              return true;
            }
          }
          return false;
        });
        if (!matchesActivity) return false;
      }
      return true;
    });
  }, [rawOffers, selectedBranchId, selectedActivityTypeId, allPlans]);

  useEffect(() => {
    setSelectedActivityTypeId((currentId) => {
      if (!currentId || currentId === "all") return currentId;
      const currentTypeExists = activityTypes.some(
        (activityType) => String(activityType.id) === String(currentId),
      );
      return currentTypeExists ? currentId : "";
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
   * Creates the subscription or subscribes member to an offer.
   */
  async function handleCreateSubscription(values) {
    setFormError("");

    try {
      if (values.offer_id) {
        await subscribeOffer({
          id: values.offer_id,
          body: {
            member_id: Number(values.member_id),
            plan_id: values.plan_id ? Number(values.plan_id) : undefined,
            paid_amount: Number(values.paid_amount),
            payment_method: values.payment_method || "cash",
            receipt_number: values.receipt_number || undefined,
            start_date: values.start_date || undefined,
            end_date: values.end_date || undefined,
            months_count: Number(values.months_count) || 1,
            notes: values.notes || undefined,
          },
        }).unwrap();
        toast.success("تم تسجيل اشتراك اللاعب في العرض بنجاح!");
        return true;
      }

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
    offers,
    activityTypes,
    selectedActivityTypeId,
    setSelectedActivityTypeId,
    isPlansLoading: isPlansLoading || isPlansFetching,
    plansErrorMessage: plansError
      ? getApiErrorMessage(plansError, "تعذر تحميل باقات الاشتراك المتاحة.")
      : "",
    isOffersLoading: isOffersLoading || isOffersFetching,
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
    isCreating: isCreating || isSubscribingOffer,
    isUpdating,
    handleCreateSubscription,
    handleUpdateSubscription,
  };
}

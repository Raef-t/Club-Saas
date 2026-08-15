import { isSubscriptionPlanSuspended } from "./subscriptionPlanSuspension";

export const SUBSCRIPTION_PLAN_STATUS = {
  ACTIVE: "active",
  INACTIVE: "inactive",
  COMPLETED: "completed",
  SUSPENDED: "suspended",
};

const STATUS_META = {
  [SUBSCRIPTION_PLAN_STATUS.ACTIVE]: {
    label: "نشطة",
    className: "status-success",
  },
  [SUBSCRIPTION_PLAN_STATUS.INACTIVE]: {
    label: "غير نشطة",
    className: "status-danger",
  },
  [SUBSCRIPTION_PLAN_STATUS.COMPLETED]: {
    label: "مكتملة",
    className: "bg-app-yellow/15 text-app-yellow",
  },
  [SUBSCRIPTION_PLAN_STATUS.SUSPENDED]: {
    label: "موقوفة مؤقتاً",
    className: "bg-app-yellow/15 text-app-yellow",
  },
};

/**
 * Resolves the three supported backend statuses while keeping compatibility
 * with older responses that only expose an is_active boolean.
 */
export function getSubscriptionPlanStatus(plan) {
  const status = String(plan?.status || "").toLowerCase();

  if (
    [
      SUBSCRIPTION_PLAN_STATUS.ACTIVE,
      SUBSCRIPTION_PLAN_STATUS.INACTIVE,
      SUBSCRIPTION_PLAN_STATUS.COMPLETED,
    ].includes(status)
  ) {
    return status;
  }

  return plan?.is_active === false
    ? SUBSCRIPTION_PLAN_STATUS.INACTIVE
    : SUBSCRIPTION_PLAN_STATUS.ACTIVE;
}

export function getSubscriptionPlanStatusMeta(planOrStatus) {
  if (typeof planOrStatus === "object" && isSubscriptionPlanSuspended(planOrStatus)) {
    return {
      status: SUBSCRIPTION_PLAN_STATUS.SUSPENDED,
      ...STATUS_META[SUBSCRIPTION_PLAN_STATUS.SUSPENDED],
    };
  }

  const status =
    typeof planOrStatus === "string"
      ? getSubscriptionPlanStatus({ status: planOrStatus })
      : getSubscriptionPlanStatus(planOrStatus);

  return {
    status,
    ...STATUS_META[status],
  };
}

export function isSubscriptionPlanActive(plan) {
  return (
    getSubscriptionPlanStatus(plan) === SUBSCRIPTION_PLAN_STATUS.ACTIVE &&
    !isSubscriptionPlanSuspended(plan)
  );
}

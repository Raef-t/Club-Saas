const SUSPENSION_COLLECTION_KEYS = [
  "suspensions",
  "subscription_plan_suspensions",
  "plan_suspensions",
  "suspension_history",
];

const ACTIVE_SUSPENSION_KEYS = [
  "active_suspension",
  "current_suspension",
  "latest_suspension",
  "suspension",
];

function isActiveSuspensionRecord(suspension) {
  if (!suspension || typeof suspension !== "object") return false;
  if (suspension.deleted_at || suspension.actual_end_date) return false;

  const status = String(suspension.status || "active").toLowerCase();
  return !["cancelled", "canceled", "completed", "ended", "inactive"].includes(status);
}

/**
 * Returns the active suspension record regardless of the relation name used by
 * the backend's list and details responses.
 */
export function getActiveSubscriptionPlanSuspension(plan) {
  if (!plan || typeof plan !== "object") return null;

  for (const key of ACTIVE_SUSPENSION_KEYS) {
    if (isActiveSuspensionRecord(plan[key])) return plan[key];
  }

  for (const key of SUSPENSION_COLLECTION_KEYS) {
    const suspensions = Array.isArray(plan[key]) ? plan[key] : [];
    const activeSuspension = suspensions.find(isActiveSuspensionRecord);
    if (activeSuspension) return activeSuspension;
  }

  const suspensionId = plan.active_suspension_id ?? plan.suspension_id;
  if (suspensionId && plan.is_suspended !== false) {
    return { id: suspensionId, status: "active" };
  }

  return null;
}

export function isSubscriptionPlanSuspended(plan) {
  return Boolean(
    plan?.is_suspended === true ||
    String(plan?.status || "").toLowerCase() === "suspended" ||
    getActiveSubscriptionPlanSuspension(plan),
  );
}

export function getSubscriptionPlanSuspensionId(plan) {
  return getActiveSubscriptionPlanSuspension(plan)?.id ?? null;
}

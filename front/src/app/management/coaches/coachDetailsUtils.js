import { formatLocalizedName } from "../../../lib/utils";
import { calculateAge, COACH_ACTIVITY_KINDS, getCoachActivityKind } from "./coachFormUtils";

const COMMISSION_PAYMENT_TYPES = new Set(["commission", "commission_based"]);

export function getCoachActivityPlanKey(coachId, activityId) {
  return `${String(coachId)}:${String(activityId)}`;
}

/**
 * Groups subscription plans by both coach and activity so coaches sharing the
 * same activity never see each other's linked events.
 */
export function createCoachActivityPlansMap(plans = []) {
  const map = new Map();

  plans.forEach((plan) => {
    const planActivities = Array.isArray(plan?.activities) ? plan.activities : [];

    planActivities.forEach((item) => {
      const activityId = item?.activity_id ?? item?.activity?.id;
      const coachId = item?.coach_id ?? item?.coach?.id ?? item?.coach?.coach_id;
      if (activityId == null || coachId == null) return;

      const key = getCoachActivityPlanKey(coachId, activityId);
      if (!map.has(key)) map.set(key, []);
      map.get(key).push({
        id: plan.id,
        name: formatLocalizedName(plan.name),
        price: plan.base_price,
        sessions: plan.session_count,
      });
    });
  });

  return map;
}

/**
 * Uses the age returned by the API and falls back to calculating it from DOB.
 */
export function getCoachAge(coach) {
  const apiAge = Number(coach?.person?.age ?? coach?.age);
  if (Number.isFinite(apiAge) && apiAge >= 0) return Math.floor(apiAge);

  return calculateAge(coach?.person?.dob);
}

/**
 * Resolves coach branch identifiers into localized display names.
 */
export function getCoachBranchNames(coach, branches = []) {
  const branchIds = Array.isArray(coach?.branch_ids) ? coach.branch_ids : [];

  return (
    branchIds
      .map((id) => {
        const branch = branches.find((candidate) => String(candidate.id) === String(id));
        return branch ? formatLocalizedName(branch.name) : `فرع #${id}`;
      })
      .join("، ") || "-"
  );
}

/** Identifies coaches assigned to private equipment training. */
export function isPrivateEquipmentCoach(coach) {
  const activities = Array.isArray(coach?.activities) ? coach.activities : [];
  const hasPrivateEquipmentActivity = activities.some(
    (activity) =>
      Boolean(Number(activity?.is_private_equipment)) ||
      getCoachActivityKind(activity) === COACH_ACTIVITY_KINDS.PRIVATE_TRAINING,
  );
  const hasCompensatedActivity = activities.some((activity) => {
    const kind = getCoachActivityKind(activity);
    return (
      kind === COACH_ACTIVITY_KINDS.GENERAL_TRAINING || kind === COACH_ACTIVITY_KINDS.GROUP_CLASS
    );
  });
  if (hasPrivateEquipmentActivity) return !hasCompensatedActivity;

  const workTypes = Array.isArray(coach?.work_types)
    ? coach.work_types
    : Array.isArray(coach?.details?.work_types)
      ? coach.details.work_types
      : [];
  const paymentType = coach?.details?.payment_type || coach?.employment_type || "";

  return (
    workTypes.includes("equipment") &&
    !workTypes.includes("activities") &&
    COMMISSION_PAYMENT_TYPES.has(paymentType)
  );
}

/**
 * Resolves which compensation fields should be visible for a coach.
 */
export function getCoachCompensationVisibility(coach) {
  const paymentType = coach?.details?.payment_type || coach?.employment_type || "";
  const isPrivateEquipment = isPrivateEquipmentCoach(coach);

  return {
    paymentType,
    isPrivateEquipment,
    showSalary: paymentType === "hybrid" || !COMMISSION_PAYMENT_TYPES.has(paymentType),
    showCommission: paymentType === "hybrid" || COMMISSION_PAYMENT_TYPES.has(paymentType),
  };
}

/**
 * Formats a commission rate without unnecessary trailing zeroes.
 */
export function formatCoachCommission(value) {
  const rate = Number(value);
  if (!Number.isFinite(rate)) return "-";

  return `${rate.toLocaleString("en-US", { maximumFractionDigits: 2 })}%`;
}

/**
 * Resolves the distinct activity and private-training commission rates that
 * should be shown for a coach across list and details views.
 */
export function getCoachCommissionItems(coach) {
  const activities = Array.isArray(coach?.activities) ? coach.activities : [];
  const workTypes = Array.isArray(coach?.work_types)
    ? coach.work_types
    : Array.isArray(coach?.details?.work_types)
      ? coach.details.work_types
      : [];
  const activityRate = coach?.details?.default_commission_rate ?? coach?.default_commission_rate;
  const privateRate = coach?.details?.private_commission_rate ?? coach?.private_commission_rate;
  const hasGroupClass = activities.some(
    (activity) => getCoachActivityKind(activity) === COACH_ACTIVITY_KINDS.GROUP_CLASS,
  );
  const hasPrivateTraining = activities.some(
    (activity) =>
      Boolean(Number(activity?.is_private_equipment)) ||
      getCoachActivityKind(activity) === COACH_ACTIVITY_KINDS.PRIVATE_TRAINING,
  );
  const showPrivate =
    hasPrivateTraining || (workTypes.includes("equipment") && Number(privateRate) > 0);
  const showActivity = hasGroupClass || workTypes.includes("activities");
  const items = [];

  if (showActivity) {
    items.push({
      key: "activity",
      label: "نسبة المدرب من الفعالية",
      shortLabel: "فعالية",
      value: formatCoachCommission(activityRate),
    });
  }
  if (showPrivate) {
    items.push({
      key: "private",
      label: "نسبة المدرب من التدريب الخاص",
      shortLabel: "خاص",
      value: formatCoachCommission(privateRate),
    });
  }

  if (items.length === 0 && activityRate != null) {
    items.push({
      key: "commission",
      label: "نسبة المدرب",
      shortLabel: "نسبة",
      value: formatCoachCommission(activityRate),
    });
  }

  return items;
}

/**
 * Returns activities that have not yet been assigned to the coach.
 */
export function getUnassignedActivities(coachActivities = [], activities = []) {
  const assignedIds = new Set(coachActivities.map(({ id }) => String(id)));
  return activities.filter(({ id }) => !assignedIds.has(String(id)));
}

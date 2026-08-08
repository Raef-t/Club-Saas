import { formatLocalizedName } from "../../../lib/utils";
import { calculateAge, COACH_ACTIVITY_KINDS, getCoachActivityKind } from "./coachFormUtils";

const COMMISSION_PAYMENT_TYPES = new Set(["commission", "commission_based"]);

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

/**
 * Identifies private-equipment coaches, who do not receive a salary or commission.
 */
export function isPrivateEquipmentCoach(coach) {
  const activities = Array.isArray(coach?.activities) ? coach.activities : [];
  const hasPrivateEquipmentActivity = activities.some(
    (activity) =>
      Boolean(Number(activity?.is_private_equipment)) ||
      getCoachActivityKind(activity) === COACH_ACTIVITY_KINDS.PRIVATE_TRAINING,
  );
  if (hasPrivateEquipmentActivity) return true;

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

  if (isPrivateEquipment) {
    return {
      paymentType,
      isPrivateEquipment: true,
      showSalary: false,
      showCommission: false,
    };
  }

  return {
    paymentType,
    isPrivateEquipment: false,
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
 * Returns activities that have not yet been assigned to the coach.
 */
export function getUnassignedActivities(coachActivities = [], activities = []) {
  const assignedIds = new Set(coachActivities.map(({ id }) => String(id)));
  return activities.filter(({ id }) => !assignedIds.has(String(id)));
}

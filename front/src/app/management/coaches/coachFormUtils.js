import { resolveWorkStatus } from "@/lib/workStatus";

/**
 * Maps the coach details response to the controlled edit-form values.
 */
export function createCoachEditInitialValues(coach) {
  if (!coach) return null;

  const branchIds = Array.isArray(coach.branch_ids)
    ? coach.branch_ids.map(Number)
    : Array.isArray(coach.branches)
      ? coach.branches.map((branch) => Number(branch.id))
      : [];
  const activityIds = Array.isArray(coach.activities)
    ? coach.activities.map((activity) => Number(activity.id))
    : Array.isArray(coach.activity_ids)
      ? coach.activity_ids.map(Number)
      : [];
  const coachShifts = Array.isArray(coach.shifts)
    ? coach.shifts
    : Array.isArray(coach.details?.shifts)
      ? coach.details.shifts
      : [];
  const shifts = coachShifts
    .map((shift) =>
      Number(
        typeof shift === "object"
          ? shift.branch_shift_id || shift.branch_shift?.id || shift.id
          : shift,
      ),
    )
    .filter((id) => Number.isFinite(id) && id > 0);
  const workTypes = coach.work_types || coach.details?.work_types || [];
  const [firstName, ...remainingNameParts] = (coach.person?.full_name || "").split(/\s+/);
  const primaryContact = coach.person?.contacts?.[0] || null;
  const commissionRate =
    coach.default_commission_rate ?? coach.details?.default_commission_rate ?? 0;
  const privateCommissionRate =
    coach.private_commission_rate ?? coach.details?.private_commission_rate ?? commissionRate;

  return {
    first_name: coach.person?.first_name || firstName || "",
    last_name: coach.person?.last_name || remainingNameParts.join(" ") || "",
    gender: coach.person?.gender || "male",
    dob: coach.person?.dob ? coach.person.dob.split("T")[0] : "",
    phone_number:
      coach.person?.phone_number || coach.person?.phone || primaryContact?.phone_number || "",
    country_code: coach.person?.country_code || primaryContact?.country_code || "+963",
    address: coach.person?.address || "",
    branch_ids: branchIds,
    experience_years: String(coach.experience_years || coach.details?.experience_years || 0),
    start_date: (coach.start_date || coach.details?.start_date || "").split("T")[0],
    work_status: resolveWorkStatus(coach),
    is_active: resolveWorkStatus(coach) === "active",
    employment_type: coach.employment_type || "fixed_salary",
    base_salary: String(Number(coach.base_salary) || 0),
    default_commission_rate: String(Number(commissionRate) || 0),
    private_club_commission_rate: getComplementaryCommissionPercentage(privateCommissionRate),
    work_types: Array.isArray(workTypes) ? workTypes : [],
    activity_ids: activityIds,
    shifts,
    photo: coach.person?.photo_url || coach.person?.photo || null,
    reason: "",
  };
}

/**
 * Creates the controlled form state for coach creation and editing.
 */
export function createCoachFormInitialValues(
  initialValues,
  branches = [],
  selectedBranchId = "all",
) {
  const defaultBranchId =
    selectedBranchId !== "all" &&
    branches.some((branch) => String(branch.id) === String(selectedBranchId))
      ? Number(selectedBranchId)
      : branches[0]?.id
        ? Number(branches[0].id)
        : null;

  return {
    first_name: initialValues?.first_name || "",
    last_name: initialValues?.last_name || "",
    gender: initialValues?.gender || "male",
    dob: initialValues?.dob || "",
    phone_number: initialValues?.phone_number || "",
    country_code: initialValues?.country_code || "+963",
    address: initialValues?.address || "",
    branch_ids: initialValues?.branch_ids || (defaultBranchId ? [defaultBranchId] : []),
    experience_years: initialValues?.experience_years || "0",
    start_date: initialValues?.start_date || "",
    work_status: resolveWorkStatus(initialValues || {}),
    is_active: resolveWorkStatus(initialValues || {}) === "active",
    employment_type: initialValues?.employment_type || "fixed_salary",
    base_salary: initialValues?.base_salary || "0",
    default_commission_rate: initialValues?.default_commission_rate || "0",
    private_club_commission_rate: initialValues?.private_club_commission_rate || "",
    work_types: initialValues?.work_types || [],
    activity_ids: initialValues?.activity_ids || [],
    shifts: initialValues?.shifts || [],
    reason: "",
  };
}

export const COACH_ACTIVITY_KINDS = {
  GENERAL_TRAINING: "general_training",
  PRIVATE_TRAINING: "private_training",
  GROUP_CLASS: "group_class",
  DAILY_ENTRY: "daily_entry",
  UNKNOWN: "unknown",
};

/** Returns the complementary share that makes the club and coach total 100%. */
export function getComplementaryCommissionPercentage(percentage) {
  if (String(percentage ?? "").trim() === "") return "";

  const numericPercentage = Number(percentage);
  if (!Number.isFinite(numericPercentage) || numericPercentage < 0 || numericPercentage > 100) {
    return "";
  }

  return String(Number((100 - numericPercentage).toFixed(2)));
}

function getLocalizedValues(value) {
  if (!value) return [];
  if (typeof value === "string" || typeof value === "number") return [String(value)];
  if (typeof value === "object") return Object.values(value).filter(Boolean).map(String);
  return [];
}

function normalizeActivityTypeText(value) {
  return value
    .toLowerCase()
    .normalize("NFKD")
    .replace(/[\u064b-\u065f\u0670]/g, "")
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

/**
 * Resolves the backend activity type across its nested and flat response shapes.
 */
export function getCoachActivityKind(activity) {
  if (activity?.is_private_equipment === true || Number(activity?.is_private_equipment) === 1) {
    return COACH_ACTIVITY_KINDS.PRIVATE_TRAINING;
  }

  const activityType = activity?.activity_type || activity?.type || {};
  const candidates = [
    activity?.name,
    activity?.activity_type_code,
    activity?.activity_type_slug,
    activity?.activity_type_name,
    activityType?.code,
    activityType?.slug,
    activityType?.key,
    activityType?.type,
    activityType?.name,
  ].flatMap(getLocalizedValues);
  const typeText = normalizeActivityTypeText(candidates.join(" "));

  if (
    typeText.includes("daily entry") ||
    typeText.includes("day entry") ||
    typeText.includes("دخول يومي")
  ) {
    return COACH_ACTIVITY_KINDS.DAILY_ENTRY;
  }

  if (
    typeText.includes("group class") ||
    typeText.includes("group session") ||
    typeText.includes("group training") ||
    typeText.includes("فعالية") ||
    typeText.includes("نشاط جماعي") ||
    typeText.includes("حصة جماعية") ||
    typeText.includes("حصص جماعية") ||
    typeText.includes("تدريب جماعي")
  ) {
    return COACH_ACTIVITY_KINDS.GROUP_CLASS;
  }

  if (
    typeText.includes("private training") ||
    typeText.includes("personal training") ||
    typeText.includes("private session") ||
    typeText.includes("private equipment") ||
    typeText.includes("تدريب خاص") ||
    typeText.includes("تدريب شخصي") ||
    typeText.includes("تدريب فردي") ||
    typeText.includes("اجهزة خاص")
  ) {
    return COACH_ACTIVITY_KINDS.PRIVATE_TRAINING;
  }

  if (
    typeText.includes("general training") ||
    typeText.includes("general gym") ||
    typeText.includes("general equipment") ||
    typeText.includes("تدريب عام") ||
    typeText.includes("اجهزة عام")
  ) {
    return COACH_ACTIVITY_KINDS.GENERAL_TRAINING;
  }

  return COACH_ACTIVITY_KINDS.UNKNOWN;
}

/**
 * Derives work, compensation, and shift rules from the assigned activities.
 */
export function getCoachRulesForActivities(selectedActivities = []) {
  const kinds = new Set(selectedActivities.map(getCoachActivityKind));
  const hasGeneralTraining = kinds.has(COACH_ACTIVITY_KINDS.GENERAL_TRAINING);
  const hasPrivateTraining = kinds.has(COACH_ACTIVITY_KINDS.PRIVATE_TRAINING);
  const hasGroupClass = kinds.has(COACH_ACTIVITY_KINDS.GROUP_CLASS);
  const hasDailyEntry = kinds.has(COACH_ACTIVITY_KINDS.DAILY_ENTRY);
  const hasRecognizedActivity = hasGeneralTraining || hasPrivateTraining || hasGroupClass;
  const allowsSalary = hasGeneralTraining;
  const allowsCommission = hasPrivateTraining || hasGroupClass;
  const workTypes = [
    ...(hasGeneralTraining || hasPrivateTraining ? ["equipment"] : []),
    ...(hasGroupClass ? ["activities"] : []),
  ];
  const employmentType =
    allowsSalary && allowsCommission
      ? "hybrid"
      : allowsCommission
        ? "commission_based"
        : "fixed_salary";

  const common = {
    hasRecognizedActivity,
    hasGeneralTraining,
    hasPrivateTraining,
    hasGroupClass,
    hasDailyEntry,
    hasIncompatibleActivities: false,
  };

  if (hasRecognizedActivity) {
    return {
      ...common,
      workTypes,
      employmentType,
      allowsSalary,
      allowsCommission,
      allowsShifts: hasGeneralTraining,
    };
  }

  return {
    ...common,
    workTypes: [],
    employmentType: "fixed_salary",
    allowsSalary: false,
    allowsCommission: false,
    allowsShifts: false,
  };
}

/**
 * Resolves the employment type implied by the selected coach work types.
 */
export function getEmploymentTypeForWorkTypes(workTypes = []) {
  const hasEquipment = workTypes.includes("equipment");
  const hasActivities = workTypes.includes("activities");

  if (hasEquipment && hasActivities) return "hybrid";
  if (hasActivities) return "commission_based";
  return "fixed_salary";
}

/**
 * Calculates age in years from a date of birth string.
 */
export function calculateAge(dobString) {
  if (!dobString) return null;
  let birthDate = new Date(dobString);
  if (isNaN(birthDate.getTime()) && typeof dobString === "string") {
    const parts = dobString.split(/[-/]/);
    if (parts.length === 3 && parts[2].length === 4) {
      birthDate = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
    }
  }
  if (isNaN(birthDate.getTime())) return null;

  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age >= 0 ? age : null;
}

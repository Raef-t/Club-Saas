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
    is_active: initialValues?.is_active ?? true,
    employment_type: initialValues?.employment_type || "fixed_salary",
    base_salary: initialValues?.base_salary || "0",
    default_commission_rate: initialValues?.default_commission_rate || "0",
    work_types: initialValues?.work_types || [],
    activity_ids: initialValues?.activity_ids || [],
    shifts: initialValues?.shifts || [],
  };
}

export const COACH_ACTIVITY_KINDS = {
  GENERAL_TRAINING: "general_training",
  PRIVATE_TRAINING: "private_training",
  GROUP_CLASS: "group_class",
  DAILY_ENTRY: "daily_entry",
  UNKNOWN: "unknown",
};

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
  const activityType = activity?.activity_type || activity?.type || {};
  const candidates = [
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
    typeText.includes("تدريب خاص") ||
    typeText.includes("تدريب شخصي") ||
    typeText.includes("تدريب فردي")
  ) {
    return COACH_ACTIVITY_KINDS.PRIVATE_TRAINING;
  }

  if (
    typeText.includes("general training") ||
    typeText.includes("general gym") ||
    typeText.includes("تدريب عام")
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
  const hasIncompatibleActivities = hasGroupClass && (hasGeneralTraining || hasPrivateTraining);

  const common = {
    hasRecognizedActivity,
    hasGeneralTraining,
    hasPrivateTraining,
    hasGroupClass,
    hasDailyEntry,
    hasIncompatibleActivities,
  };

  if (hasGroupClass) {
    return {
      ...common,
      workTypes: ["activities"],
      employmentType: "commission_based",
      allowsSalary: false,
      allowsCommission: true,
      allowsShifts: false,
    };
  }

  if (hasGeneralTraining) {
    return {
      ...common,
      workTypes: ["equipment"],
      employmentType: "fixed_salary",
      allowsSalary: true,
      allowsCommission: false,
      allowsShifts: true,
    };
  }

  if (hasPrivateTraining) {
    return {
      ...common,
      workTypes: ["equipment"],
      employmentType: "commission_based",
      allowsSalary: false,
      allowsCommission: false,
      allowsShifts: false,
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

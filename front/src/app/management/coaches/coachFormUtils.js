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
    national_id: initialValues?.national_id || "",
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
    shift_ids: initialValues?.shift_ids || [],
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

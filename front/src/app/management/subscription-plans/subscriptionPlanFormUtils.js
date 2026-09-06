function getLocalizedName(name) {
  if (!name) return "";
  if (typeof name === "string") return name.trim();
  return String(name.ar || name.en || "").trim();
}

export function getSubscriptionPlanActivityName(activity) {
  return getLocalizedName(activity?.name);
}

export function getSubscriptionPlanCoachName(coach) {
  return (
    String(coach?.person?.full_name || coach?.full_name || "").trim() ||
    getLocalizedName(coach?.name)
  );
}

/** Only the general equipment activity is allowed to omit a coach. */
export function isGeneralEquipmentActivity(activity) {
  const activityType = activity?.activity_type;
  return Boolean(
    activityType &&
    activityType.is_private_equipment === false &&
    activityType.is_session_based === false &&
    activityType.is_daily_entry !== true &&
    activityType.has_unlimited_subscribers === true,
  );
}

/** General and private equipment plans do not use scheduled session times. */
export function isEquipmentActivity(activity) {
  const activityType = activity?.activity_type;
  return Boolean(
    activityType &&
    activityType.is_session_based === false &&
    activityType.is_daily_entry !== true &&
    activityType.has_unlimited_subscribers === true,
  );
}

/** Identifies private-equipment activities from the explicit backend type flag. */
export function isPrivateEquipmentActivity(activity) {
  return activity?.activity_type?.is_private_equipment === true;
}

/** Private equipment and private training plans both use coach/branch prices. */
export function isPrivateSubscriptionActivity(activity) {
  return isPrivateEquipmentActivity(activity);
}

/** Adds the two private-plan prices without allowing invalid values into the payload. */
export function calculatePrivatePlanBasePrice(coachPrice, branchPrice) {
  const coachAmount = Number(coachPrice);
  const branchAmount = Number(branchPrice);

  if (
    !Number.isFinite(coachAmount) ||
    !Number.isFinite(branchAmount) ||
    coachAmount < 0 ||
    branchAmount < 0
  ) {
    return 0;
  }

  return Number((coachAmount + branchAmount).toFixed(2));
}

/** Reads and validates the private-training commission stored on a coach record. */
export function getSubscriptionPlanCoachCommission(coach) {
  const value =
    coach?.details?.private_commission_rate ??
    coach?.private_commission_rate ??
    // Keep older coach records working when they predate the dedicated private rate.
    coach?.details?.default_commission_rate ??
    coach?.default_commission_rate;
  if (value === null || value === undefined || String(value).trim() === "") return null;

  const percentage = Number(value);
  if (!Number.isFinite(percentage) || percentage < 0 || percentage > 100) return null;
  return percentage;
}

/** Calculates one share of the activity price from a percentage. */
export function calculateCommissionAmount(price, commissionPercentage) {
  if (String(price ?? "").trim() === "" || commissionPercentage === null) return null;

  const numericPrice = Number(price);
  const numericPercentage = Number(commissionPercentage);
  if (
    !Number.isFinite(numericPrice) ||
    !Number.isFinite(numericPercentage) ||
    numericPrice < 0 ||
    numericPercentage < 0 ||
    numericPercentage > 100
  ) {
    return null;
  }

  return Number(((numericPrice * numericPercentage) / 100).toFixed(2));
}

export function createSuggestedSubscriptionPlanName(planActivities, activities, coaches) {
  const selectedItem = (planActivities || []).find((item) => item?.activity_id);
  if (!selectedItem) return "";

  const activity = (activities || []).find(
    (item) => String(item.id) === String(selectedItem.activity_id),
  );
  const activityName = getSubscriptionPlanActivityName(activity);
  if (!activityName) return "";

  const coach = (coaches || []).find((item) => String(item.id) === String(selectedItem.coach_id));
  const coachName = getSubscriptionPlanCoachName(coach);

  return coachName ? `${activityName} - ${coachName}` : activityName;
}

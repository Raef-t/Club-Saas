function getLocalizedName(name) {
  if (!name) return "";
  if (typeof name === "string") return name.trim();
  return String(name.ar || name.en || "").trim();
}

function normalizeArabicText(value) {
  return String(value || "")
    .normalize("NFKD")
    .replace(/[\u064B-\u065F\u0670]/g, "")
    .replace(/[أإآ]/g, "ا")
    .replace(/ـ/g, "")
    .replace(/\s+/g, " ")
    .trim()
    .toLowerCase();
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
  const normalizedName = normalizeArabicText(getSubscriptionPlanActivityName(activity));
  return normalizedName.includes("اجهزة عام");
}

/** General and private equipment plans do not use scheduled session times. */
export function isEquipmentActivity(activity) {
  const normalizedName = normalizeArabicText(getSubscriptionPlanActivityName(activity));
  return normalizedName.includes("اجهزة عام") || normalizedName.includes("اجهزة خاص");
}

/** Identifies private-equipment activities that use the selected coach's commission. */
export function isPrivateEquipmentActivity(activity) {
  const normalizedName = normalizeArabicText(getSubscriptionPlanActivityName(activity));
  return normalizedName.includes("اجهزة خاص");
}

/** Reads and validates the commission percentage stored on a coach record. */
export function getSubscriptionPlanCoachCommission(coach) {
  const value = coach?.default_commission_rate ?? coach?.details?.default_commission_rate;
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

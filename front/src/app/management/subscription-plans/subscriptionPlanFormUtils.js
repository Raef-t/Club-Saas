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

/** Private equipment plans use the branch's private-training club commission. */
export function isPrivateEquipmentActivity(activity) {
  const normalizedName = normalizeArabicText(getSubscriptionPlanActivityName(activity));
  return normalizedName.includes("اجهزة خاص");
}

/** Returns the coach share that completes the club share to 100%. */
export function getCoachCommissionPercentage(clubCommissionPercentage) {
  if (String(clubCommissionPercentage ?? "").trim() === "") return "";

  const clubPercentage = Number(clubCommissionPercentage);
  if (!Number.isFinite(clubPercentage)) return "";

  return String(Number((100 - clubPercentage).toFixed(2)));
}

/** Calculates a commission amount for the UI without adding it to the API payload. */
export function calculateCommissionAmount(price, commissionPercentage) {
  if (String(price ?? "").trim() === "" || String(commissionPercentage ?? "").trim() === "") {
    return null;
  }

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

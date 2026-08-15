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

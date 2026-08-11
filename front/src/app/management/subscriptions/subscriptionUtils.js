const CURRENCY_SYMBOL = "$";

const DAILY_ENTRY_TYPE_VALUES = new Set([
  "daily",
  "daily entry",
  "day pass",
  "single day",
  "walk in",
]);

function normalizePlanValue(value) {
  return String(value || "")
    .trim()
    .toLowerCase()
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ");
}

function getLocalizedValues(value) {
  if (typeof value === "string" || typeof value === "number") return [value];
  if (!value || typeof value !== "object" || Array.isArray(value)) return [];
  return Object.values(value).filter(
    (entry) => typeof entry === "string" || typeof entry === "number",
  );
}

/**
 * Returns a local calendar date in the ISO value expected by date inputs.
 */
export function getLocalDateValue(date = new Date()) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "";

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

/**
 * Identifies one-day entry plans using API type fields, with the localized
 * plan name as a fallback for older responses that do not expose a type.
 */
export function isDailyEntrySubscriptionPlan(plan) {
  if (!plan || typeof plan !== "object") return false;
  if (plan.is_daily_entry === true || plan.is_day_pass === true) return true;

  const typeValues = [plan.type, plan.plan_type, plan.subscription_type, plan.duration_type]
    .flatMap(getLocalizedValues)
    .map(normalizePlanValue);

  if (typeValues.some((value) => DAILY_ENTRY_TYPE_VALUES.has(value))) return true;

  const nameValues = [plan.name, plan.title].flatMap(getLocalizedValues).map(normalizePlanValue);

  return nameValues.some(
    (value) =>
      value.includes("دخولية") ||
      value.includes("دخول يومي") ||
      value.includes("daily entry") ||
      value.includes("day pass") ||
      value.includes("walk in"),
  );
}

/**
 * Converts an API amount into a safe finite number.
 */
export function parseSubscriptionAmount(value) {
  const amount = Number.parseFloat(value || 0);
  return Number.isFinite(amount) ? amount : 0;
}

/**
 * Formats subscription amounts using the currency shown by the dashboard.
 */
export function formatSubscriptionMoney(value) {
  return `${CURRENCY_SYMBOL}${parseSubscriptionAmount(value).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}`;
}

/**
 * Extracts the subscription list from the supported backend response shapes.
 */
export function getSubscriptionRows(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  return [];
}

/**
 * Extracts a single subscription from its backend response.
 */
export function getSubscriptionDetail(response) {
  return response?.data || null;
}

function getSubscriptionMemberId(subscription) {
  return (
    subscription?.member_id ??
    subscription?.player_id ??
    subscription?.member?.id ??
    subscription?.player?.id ??
    null
  );
}

/**
 * Selects the most relevant subscription for a member from a list response.
 */
export function getCurrentMemberSubscription(response, memberId) {
  const rows = getSubscriptionRows(response);
  const hasMemberIdentifiers = rows.some((subscription) => getSubscriptionMemberId(subscription));
  const memberRows = hasMemberIdentifiers
    ? rows.filter(
        (subscription) => String(getSubscriptionMemberId(subscription)) === String(memberId),
      )
    : rows;
  const statusPriority = { active: 0, frozen: 1, pending: 2 };

  return (
    [...memberRows].sort((first, second) => {
      const firstPriority = statusPriority[first.status] ?? 3;
      const secondPriority = statusPriority[second.status] ?? 3;
      if (firstPriority !== secondPriority) return firstPriority - secondPriority;

      const firstDate = new Date(first.start_date || first.created_at || 0).getTime() || 0;
      const secondDate = new Date(second.start_date || second.created_at || 0).getTime() || 0;
      if (firstDate !== secondDate) return secondDate - firstDate;

      return Number(second.id || 0) - Number(first.id || 0);
    })[0] || null
  );
}

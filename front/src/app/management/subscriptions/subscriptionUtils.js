import { formatMoney } from "@/lib/utils";

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
 * Calculates an inclusive subscription period. The end date is the calendar
 * anniversary after the requested number of months, minus one day.
 */
export function getSubscriptionEndDate(startDate, months = 1) {
  const match = String(startDate || "").match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return "";

  const year = Number(match[1]);
  const monthIndex = Number(match[2]) - 1;
  const day = Number(match[3]);
  const start = new Date(year, monthIndex, day);

  if (start.getFullYear() !== year || start.getMonth() !== monthIndex || start.getDate() !== day) {
    return "";
  }

  const normalizedMonths = Math.max(1, Number.parseInt(months, 10) || 1);
  const targetMonthStart = new Date(year, monthIndex + normalizedMonths, 1);
  const lastTargetDay = new Date(
    targetMonthStart.getFullYear(),
    targetMonthStart.getMonth() + 1,
    0,
  ).getDate();
  const anniversary = new Date(
    targetMonthStart.getFullYear(),
    targetMonthStart.getMonth(),
    Math.min(day, lastTargetDay),
  );

  anniversary.setDate(anniversary.getDate() - 1);
  return getLocalDateValue(anniversary);
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
  return formatMoney(parseSubscriptionAmount(value));
}

/**
 * Identifies plans whose price is split between the coach service and the branch.
 * API amounts are commonly returned as non-empty decimal strings.
 */
export function isPrivateSubscriptionPlan(plan) {
  return Boolean(plan?.coach_price && plan?.branch_price);
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
 * Keeps the newest subscriptions first without mutating the API response.
 * Creation time is authoritative, with the start date and numeric id retained
 * as fallbacks for older response shapes.
 */
export function sortSubscriptionsNewestFirst(rows = []) {
  function getSortValue(subscription) {
    const dateValue = subscription?.created_at || subscription?.start_date;
    const timestamp = dateValue ? Date.parse(dateValue) : Number.NaN;
    if (Number.isFinite(timestamp)) return timestamp;
    return Number(subscription?.id) || 0;
  }

  return [...rows].sort((first, second) => getSortValue(second) - getSortValue(first));
}

/** Builds the supported query for plans available to a new subscription. */
export function getAvailableSubscriptionPlanParams(branchId, activityTypeId) {
  return {
    ...(branchId && branchId !== "all" ? { branch_id: branchId } : {}),
    available: true,
    ...(activityTypeId && activityTypeId !== "all" ? { activity_type_id: activityTypeId } : {}),
    per_page: 15,
    page: 1,
  };
}

function getFiniteAmount(value, fallback = 0) {
  if (value === null || value === undefined || value === "") return fallback;
  const amount = Number(value);
  return Number.isFinite(amount) ? amount : fallback;
}

/**
 * Extracts the aggregate subscription statistics returned by the list endpoint.
 * Local fallbacks keep older API responses usable, but the paginated endpoint
 * statistics remain the source of truth whenever they are available.
 */
export function getSubscriptionStats(response, rows = getSubscriptionRows(response)) {
  const responseStats = response?.stats || response?.data?.stats || {};
  const paginationTotal = response?.meta?.total ?? response?.data?.meta?.total;
  const activeFallback = rows.filter((subscription) => subscription.status === "active").length;
  const paidFallback = rows.reduce(
    (total, subscription) => total + parseSubscriptionAmount(subscription.paid_amount),
    0,
  );

  return {
    activeSubscriptions: getFiniteAmount(responseStats.active_subscriptions, activeFallback),
    totalSubscriptions: getFiniteAmount(
      responseStats.total_subscriptions,
      getFiniteAmount(paginationTotal, rows.length),
    ),
    totalPaidAmount: getFiniteAmount(responseStats.total_paid_amount, paidFallback),
    todayRevenue: getFiniteAmount(responseStats.today_revenue),
  };
}

/**
 * Extracts a single subscription from its backend response.
 */
export function getSubscriptionDetail(response) {
  return response?.data || null;
}

/**
 * Returns the employee name attached to the subscription creator payload.
 */
export function getSubscriptionCreatorName(subscription) {
  const creator = subscription?.created_by;

  if (typeof creator === "string") return creator.trim() || null;
  if (!creator || typeof creator !== "object") return null;

  const name = creator.name || creator.full_name;
  return typeof name === "string" && name.trim() ? name.trim() : null;
}

/**
 * Returns the receipt number from the subscription or its related payment data.
 */
export function getSubscriptionReceiptNumber(subscription) {
  const candidates = [
    subscription?.receipt_number,
    ...(Array.isArray(subscription?.payments)
      ? subscription.payments.map((payment) => payment?.receipt_number)
      : []),
    ...(Array.isArray(subscription?.invoices)
      ? subscription.invoices.flatMap((invoice) => [
          invoice?.receipt_number,
          ...(Array.isArray(invoice?.payments)
            ? invoice.payments.map((payment) => payment?.receipt_number)
            : []),
        ])
      : []),
  ];

  return (
    candidates.find((value) => value !== null && value !== undefined && String(value).trim()) ??
    null
  );
}

/**
 * Normalizes the three receipt fields exposed by subscription detail responses.
 */
export function getSubscriptionReceiptNumbers(subscription) {
  const revenueSplit = subscription?.revenue_split;

  return {
    receiptNumber: getSubscriptionReceiptNumber(subscription),
    coachReceiptNumber:
      subscription?.coach_receipt_number ?? revenueSplit?.coach_receipt_number ?? null,
    branchReceiptNumber:
      subscription?.branch_receipt_number ?? revenueSplit?.branch_receipt_number ?? null,
  };
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
  const statusPriority = { active: 0, frozen: 1 };

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

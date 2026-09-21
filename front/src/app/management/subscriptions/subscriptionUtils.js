import { formatMoney } from "@/lib/utils";

const DAILY_ENTRY_TYPE_VALUES = new Set([
  "daily",
  "daily entry",
  "day pass",
  "single day",
  "walk in",
]);

const PRIVATE_TRAINING_TYPE_VALUES = [
  "private training",
  "private equipment",
  "تدريب خاص",
  "أجهزة خاص",
];

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

function roundSubscriptionAmount(value) {
  return Number(parseSubscriptionAmount(value).toFixed(2));
}

function clampSubscriptionAmount(value, maximum) {
  return roundSubscriptionAmount(
    Math.max(0, Math.min(parseSubscriptionAmount(maximum), parseSubscriptionAmount(value))),
  );
}

/** Returns the plan totals before any discount, including the selected duration. */
export function getSubscriptionOriginalAmounts(plan, monthsCount = 1) {
  const months = Math.max(1, Number.parseInt(monthsCount, 10) || 1);
  const basePrice = parseSubscriptionAmount(plan?.base_price ?? plan?.price);
  const hasCoachPrice = plan?.coach_price !== null && plan?.coach_price !== undefined;
  const hasBranchPrice = plan?.branch_price !== null && plan?.branch_price !== undefined;
  const coachOriginal = roundSubscriptionAmount(
    (hasCoachPrice ? parseSubscriptionAmount(plan.coach_price) : 0) * months,
  );
  let branchOriginal = roundSubscriptionAmount(
    (hasBranchPrice ? parseSubscriptionAmount(plan.branch_price) : 0) * months,
  );
  const baseTotal = roundSubscriptionAmount(basePrice * months);

  // Older private-plan responses may expose only base_price. Keep the total
  // payable by assigning that unsplit amount to the branch side.
  if (!hasCoachPrice && !hasBranchPrice && baseTotal > 0) branchOriginal = baseTotal;

  return {
    originalTotal: baseTotal || roundSubscriptionAmount(coachOriginal + branchOriginal),
    coachOriginal,
    branchOriginal,
  };
}

/** Calculates the percentage and amount when the operator enters the net price. */
export function calculateDiscountFromFinalPrice(originalTotal, enteredPrice) {
  const original = roundSubscriptionAmount(originalTotal);
  const finalPrice = clampSubscriptionAmount(enteredPrice, original);
  const discountAmount = roundSubscriptionAmount(original - finalPrice);
  const discountPercentage =
    original > 0 ? Number(((discountAmount / original) * 100).toFixed(2)) : 0;

  return { finalPrice, discountAmount, discountPercentage };
}

/** Calculates the net price and amount when the operator enters a percentage. */
export function calculateDiscountFromPercentage(originalTotal, enteredPercentage) {
  const original = roundSubscriptionAmount(originalTotal);
  const discountPercentage = Number(
    Math.max(0, Math.min(100, parseSubscriptionAmount(enteredPercentage))).toFixed(2),
  );
  const discountAmount = roundSubscriptionAmount((original * discountPercentage) / 100);
  const finalPrice = roundSubscriptionAmount(Math.max(0, original - discountAmount));

  return { finalPrice, discountAmount, discountPercentage };
}

/** Normalizes discount values returned by old and new subscription responses. */
export function getSubscriptionDiscountSummary(subscription) {
  const planTotals = getSubscriptionOriginalAmounts(subscription?.plan, subscription?.months_count);
  const apiOriginal = parseSubscriptionAmount(subscription?.original_total_amount);
  const offerPrice = subscription?.offer_id || subscription?.offer?.id
    ? parseSubscriptionAmount(subscription?.offer?.price)
    : 0;
  const originalTotal = apiOriginal || offerPrice || planTotals.originalTotal;
  const apiDiscountAmount = parseSubscriptionAmount(subscription?.discount_amount);
  const apiPercentage = parseSubscriptionAmount(subscription?.discount_percentage);
  const apiTotal = parseSubscriptionAmount(subscription?.total_amount);
  const calculated = apiPercentage
    ? calculateDiscountFromPercentage(originalTotal, apiPercentage)
    : calculateDiscountFromFinalPrice(
        originalTotal,
        apiTotal || Math.max(0, originalTotal - apiDiscountAmount),
      );
  const isDiscount =
    subscription?.is_discount === true ||
    Number(subscription?.is_discount) === 1 ||
    apiPercentage > 0 ||
    apiDiscountAmount > 0;

  return {
    isDiscount,
    originalTotal,
    finalPrice: isDiscount ? calculated.finalPrice : apiTotal || originalTotal,
    discountAmount: isDiscount ? calculated.discountAmount : 0,
    discountPercentage: isDiscount ? calculated.discountPercentage : 0,
  };
}

/**
 * Formats subscription amounts using the currency shown by the dashboard.
 */
export function formatSubscriptionMoney(value) {
  return formatMoney(parseSubscriptionAmount(value));
}

function hasPrivateEquipmentFlag(record) {
  if (!record || typeof record !== "object" || !("is_private_equipment" in record)) {
    return null;
  }

  return record.is_private_equipment === true || Number(record.is_private_equipment) === 1;
}

function matchesPrivateTrainingType(record) {
  if (!record || typeof record !== "object") return false;

  const explicitFlag = hasPrivateEquipmentFlag(record);
  if (explicitFlag !== null) return explicitFlag;

  const values = [record.code, record.slug, record.type, record.name, record.title]
    .flatMap(getLocalizedValues)
    .map(normalizePlanValue);

  return values.some((value) =>
    PRIVATE_TRAINING_TYPE_VALUES.some((privateType) => value.includes(privateType)),
  );
}

/**
 * Identifies private-training (private-equipment) plans from their activity type.
 * Coach/branch prices alone are not sufficient because other subscription types
 * may also expose split prices.
 */
export function isPrivateSubscriptionPlan(plan, selectedActivityType = null) {
  if (plan && typeof plan === "object") {
    const planFlag = hasPrivateEquipmentFlag(plan);
    if (planFlag === true) return true;
    if (
      plan.coach_price !== null &&
      plan.coach_price !== undefined &&
      plan.branch_price !== null &&
      plan.branch_price !== undefined
    ) {
      return true;
    }
  }

  if (selectedActivityType && typeof selectedActivityType === "object") {
    return matchesPrivateTrainingType(selectedActivityType);
  }

  if (!plan || typeof plan !== "object") return false;

  const planFlag = hasPrivateEquipmentFlag(plan);
  if (planFlag !== null) return planFlag;

  const relatedActivityTypes = [
    plan.activity_type,
    ...(Array.isArray(plan.activity_types) ? plan.activity_types : []),
    ...(Array.isArray(plan.activities)
      ? plan.activities.flatMap((activity) => [activity?.activity_type, activity])
      : []),
  ].filter(Boolean);

  if (relatedActivityTypes.length > 0) {
    return relatedActivityTypes.some(matchesPrivateTrainingType);
  }

  return [plan.code, plan.slug, plan.type, plan.plan_type, plan.subscription_type]
    .flatMap(getLocalizedValues)
    .map(normalizePlanValue)
    .some((value) =>
      PRIVATE_TRAINING_TYPE_VALUES.some((privateType) => value.includes(privateType)),
    );
}

/**
 * Resolves the private-plan amounts required when updating its two receipts.
 * Existing split payments are authoritative while the original plan is kept.
 * A changed plan always uses its own coach and branch prices.
 */
export function getSubscriptionSplitPaymentAmounts(
  subscription,
  plan = subscription?.plan,
  paidAmount = subscription?.paid_amount,
  useExistingPaymentAmounts = true,
) {
  const coachPrice = parseSubscriptionAmount(plan?.coach_price);
  const branchPrice = parseSubscriptionAmount(plan?.branch_price);

  if (!useExistingPaymentAmounts) {
    return { coachPaidAmount: coachPrice, branchPaidAmount: branchPrice };
  }

  const invoicePayments = Array.isArray(subscription?.invoices)
    ? subscription.invoices.flatMap((invoice) =>
        Array.isArray(invoice?.payments) ? invoice.payments : [],
      )
    : [];
  const payments = Array.isArray(subscription?.payments) ? subscription.payments : invoicePayments;
  const normalizedReason = (payment) =>
    String(payment?.reason || "")
      .trim()
      .toLowerCase();
  const coachPayment = payments.find((payment) => {
    const reason = normalizedReason(payment);
    return reason.includes("المدرب") || reason.includes("الكوتش") || reason.includes("coach");
  });
  const branchPayment = payments.find((payment) => {
    const reason = normalizedReason(payment);
    return reason.includes("النادي") || reason.includes("الفرع") || reason.includes("branch");
  });
  const revenueSplit = subscription?.revenue_split;
  const coachAmountSource =
    subscription?.coach_paid_amount ?? revenueSplit?.coach_paid_amount ?? coachPayment?.amount;
  const branchAmountSource =
    subscription?.branch_paid_amount ?? revenueSplit?.branch_paid_amount ?? branchPayment?.amount;
  const hasCoachAmount = coachAmountSource !== null && coachAmountSource !== undefined;
  const hasBranchAmount = branchAmountSource !== null && branchAmountSource !== undefined;
  const totalPaid = parseSubscriptionAmount(paidAmount);

  if (hasCoachAmount || hasBranchAmount) {
    const coachPaidAmount = hasCoachAmount
      ? parseSubscriptionAmount(coachAmountSource)
      : Math.max(0, totalPaid - parseSubscriptionAmount(branchAmountSource));
    const branchPaidAmount = hasBranchAmount
      ? parseSubscriptionAmount(branchAmountSource)
      : Math.max(0, totalPaid - coachPaidAmount);

    return { coachPaidAmount, branchPaidAmount };
  }

  const splitPriceTotal = coachPrice + branchPrice;

  if (totalPaid > 0 && splitPriceTotal > 0) {
    const coachPaidAmount = Number(((totalPaid * coachPrice) / splitPriceTotal).toFixed(2));
    return {
      coachPaidAmount,
      branchPaidAmount: Number((totalPaid - coachPaidAmount).toFixed(2)),
    };
  }

  return {
    coachPaidAmount: coachPrice,
    branchPaidAmount: branchPrice,
  };
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

/** Selects general training as the initial activity type for subscription forms. */
export function getDefaultSubscriptionActivityTypeId(activityTypes = []) {
  const generalTraining = activityTypes.find((activityType) => {
    const names =
      activityType?.name && typeof activityType.name === "object"
        ? Object.values(activityType.name)
        : [activityType?.name];
    const searchableText = [activityType?.code, activityType?.slug, activityType?.type, ...names]
      .filter(Boolean)
      .join(" ")
      .trim()
      .toLowerCase()
      .replace(/[_-]+/g, " ");

    return searchableText.includes("general training") || searchableText.includes("تدريب عام");
  });

  const selectedType = generalTraining || activityTypes[0];
  return selectedType?.id === undefined || selectedType?.id === null ? "" : String(selectedType.id);
}

/** Labels the current subscription revenue card with an explicit month number. */
export function getSubscriptionRevenueMonthLabel(date = new Date()) {
  const monthNumber = new Intl.NumberFormat("ar-SY", { useGrouping: false }).format(
    date.getMonth() + 1,
  );
  return `إجمالي إيرادات الشهر ${monthNumber}`;
}

/** Reads the activity type already assigned to a subscription's plan. */
export function getSubscriptionActivityTypeId(subscription) {
  const plan = subscription?.plan;
  const activityTypeId =
    plan?.activity_types?.find((activityType) => activityType?.id != null)?.id ??
    plan?.activities?.find((activity) => activity?.activity_type_id != null)?.activity_type_id;

  return activityTypeId === undefined || activityTypeId === null ? "" : String(activityTypeId);
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

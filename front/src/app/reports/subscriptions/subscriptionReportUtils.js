import { formatLocalizedName } from "@/lib/utils";

export const SUBSCRIPTION_REPORT_STATUS_OPTIONS = [
  { value: "all", label: "كل حالات الاشتراك" },
  { value: "active", label: "فعال" },
  { value: "finished", label: "منتهي" },
  { value: "frozen", label: "مجمّد" },
  { value: "terminated", label: "ملغى" },
];

export const SUBSCRIPTION_REPORT_PAYMENT_OPTIONS = [
  { value: "all", label: "كل حالات الدفع" },
  { value: "paid", label: "مدفوع بالكامل" },
  { value: "partially_paid", label: "مدفوع جزئياً" },
  { value: "unpaid", label: "غير مدفوع" },
];

export const EMPTY_SUBSCRIPTIONS_REPORT_SUMMARY = {
  total_subscriptions: 0,
  total_revenue: 0,
  total_paid: 0,
  total_remaining: 0,
  active_count: 0,
  finished_count: 0,
  frozen_count: 0,
  terminated_count: 0,
  fully_paid_count: 0,
  partially_paid_count: 0,
  unpaid_count: 0,
  currency: "SYP",
  currency_type: "SYP",
};

const STATUS_LABELS = {
  active: "فعال",
  finished: "منتهي",
  expired: "منتهي",
  frozen: "مجمّد",
  terminated: "ملغى",
  cancelled: "ملغى",
  canceled: "ملغى",
};

const PAYMENT_STATUS_LABELS = {
  paid: "مدفوع بالكامل",
  fully_paid: "مدفوع بالكامل",
  partially_paid: "مدفوع جزئياً",
  partial: "مدفوع جزئياً",
  unpaid: "غير مدفوع",
};

function toFiniteNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function firstValue(...values) {
  return values.find((value) => value !== undefined && value !== null && value !== "");
}

function getDisplayName(value, fallback = "-") {
  const formatted = formatLocalizedName(value);
  return formatted === "-" ? fallback : formatted;
}

function getPersonName(record) {
  const member = record?.member || record?.player || record?.subscriber || {};
  const person = member?.person || record?.person || {};
  const splitName = `${firstValue(member?.first_name, person?.first_name, "")} ${firstValue(
    member?.last_name,
    person?.last_name,
    "",
  )}`.trim();

  return firstValue(
    record?.member_name,
    record?.player_name,
    member?.full_name,
    person?.full_name,
    splitName,
    "-",
  );
}

function getCoachName(record) {
  const coach = record?.coach || record?.trainer || record?.plan?.coach || {};
  const person = coach?.person || {};
  const splitName = `${firstValue(coach?.first_name, person?.first_name, "")} ${firstValue(
    coach?.last_name,
    person?.last_name,
    "",
  )}`.trim();

  return firstValue(
    record?.coach_name,
    record?.trainer_name,
    coach?.full_name,
    person?.full_name,
    splitName,
    getDisplayName(coach?.name),
  );
}

export function getReportLookupCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

export function createSubscriptionsReportParams(filters = {}, branchId) {
  const params = {};

  if (filters.status && filters.status !== "all") params.status = filters.status;
  if (filters.planId) params.plan_id = filters.planId;
  if (filters.paymentStatus && filters.paymentStatus !== "all") {
    params.payment_status = filters.paymentStatus;
  }
  if (filters.coachId) params.coach_id = filters.coachId;
  if (branchId && branchId !== "all") params.branch_id = branchId;
  if (filters.startDate) params.start_date = filters.startDate;
  if (filters.endDate) params.end_date = filters.endDate;

  const search = String(filters.search || "").trim();
  if (search) params.search = search;

  return params;
}

export function normalizeSubscriptionsReportResponse(response) {
  const payload = response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};
  const summary = {
    ...EMPTY_SUBSCRIPTIONS_REPORT_SUMMARY,
    ...rawSummary,
  };

  Object.keys(EMPTY_SUBSCRIPTIONS_REPORT_SUMMARY).forEach((key) => {
    if (!key.includes("currency")) summary[key] = toFiniteNumber(summary[key]);
  });

  summary.currency = String(rawSummary.currency || rawSummary.currency_type || "SYP");
  summary.currency_type = String(rawSummary.currency_type || rawSummary.currency || "SYP");

  return {
    summary,
    records: Array.isArray(payload?.records) ? payload.records : [],
  };
}

export function normalizeSubscriptionReportRecord(record, index = 0) {
  const member = record?.member || record?.player || record?.subscriber || {};
  const person = member?.person || record?.person || {};
  const plan = record?.plan || record?.subscription_plan || {};
  const branch = record?.branch || member?.branch || plan?.branch || {};
  const status = String(firstValue(record?.status, record?.subscription_status, "")).toLowerCase();
  const paymentStatus = String(
    firstValue(record?.payment_status, record?.payment_state, ""),
  ).toLowerCase();
  const totalAmount = toFiniteNumber(
    firstValue(record?.total_amount, record?.price, record?.amount, record?.subscription_price),
  );
  const paidAmount = toFiniteNumber(firstValue(record?.paid_amount, record?.total_paid));
  const remainingAmount = toFiniteNumber(
    firstValue(record?.remaining_amount, record?.total_remaining, totalAmount - paidAmount),
  );

  return {
    id: firstValue(record?.id, record?.subscription_id, `subscription-${index}`),
    membershipNumber: firstValue(
      record?.membership_number,
      record?.member_number,
      member?.membership_number,
      member?.member_number,
      member?.id,
      "-",
    ),
    memberName: getPersonName(record),
    phone: firstValue(record?.phone, member?.phone, member?.phone_number, person?.phone, "-"),
    planName: firstValue(
      record?.plan_name,
      record?.subscription_plan_name,
      getDisplayName(plan?.name),
    ),
    coachName: getCoachName(record),
    branchName: firstValue(record?.branch_name, getDisplayName(branch?.name)),
    startDate: firstValue(record?.start_date, record?.starts_at, "-"),
    endDate: firstValue(record?.end_date, record?.ends_at, "-"),
    totalAmount,
    paidAmount,
    remainingAmount,
    status,
    statusLabel: STATUS_LABELS[status] || firstValue(record?.status_label, status, "غير محدد"),
    paymentStatus,
    paymentStatusLabel:
      PAYMENT_STATUS_LABELS[paymentStatus] ||
      firstValue(record?.payment_status_label, paymentStatus, "غير محدد"),
  };
}

export function validateSubscriptionsReportFilters(filters = {}) {
  if (filters.startDate && filters.endDate && filters.startDate > filters.endDate) {
    return "يجب أن يكون تاريخ البداية قبل تاريخ النهاية أو مساوياً له.";
  }

  return "";
}

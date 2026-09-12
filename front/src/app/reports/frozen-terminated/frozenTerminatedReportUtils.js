import { formatDate, formatMoney } from "@/lib/utils";

export const FROZEN_TERMINATED_STATUS_OPTIONS = [
  { value: "all", label: "الكل (المجمدة والملغاة)" },
  { value: "frozen", label: "المجمدة فقط (❄️)" },
  { value: "terminated", label: "الملغاة فقط (❌)" },
];

export const FROZEN_TERMINATED_DATE_FILTER_OPTIONS = [
  { value: "event_date", label: "تاريخ حدوث التجميد / الإلغاء" },
  { value: "start_date", label: "بداية الاشتراك" },
  { value: "end_date", label: "نهاية الاشتراك" },
  { value: "created_date", label: "تاريخ القيد" },
];

export const DEFAULT_FROZEN_TERMINATED_FILTERS = {
  status: "all",
  dateFilterBy: "event_date",
  startDate: "",
  endDate: "",
  planId: "",
  search: "",
};

export const EMPTY_FROZEN_TERMINATED_SUMMARY = {
  total_records: 0,
  total_frozen: 0,
  total_terminated: 0,
  total_frozen_revenue: 0,
  total_lost_terminated_revenue: 0,
  currency: "SYP",
  currency_type: "SYP",
};

export function toFiniteNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

export function getMemberPrimaryPhone(record) {
  if (record?.member_phone && record.member_phone !== "N/A" && record.member_phone !== "-") {
    return record.member_phone;
  }
  if (record?.phone && record.phone !== "N/A" && record.phone !== "-") {
    return record.phone;
  }
  if (Array.isArray(record?.contact_persons) && record.contact_persons.length > 0) {
    const contactWithPhone = record.contact_persons.find((c) => c?.phone_number);
    if (contactWithPhone?.phone_number) {
      return contactWithPhone.phone_number;
    }
  }
  return "-";
}

export function createFrozenTerminatedParams(filters = {}, branchId) {
  const params = {};

  if (filters.status && filters.status !== "all") {
    params.status = filters.status;
  } else if (filters.status === "all") {
    params.status = "all";
  }

  if (filters.dateFilterBy) {
    params.date_filter_by = filters.dateFilterBy;
  }

  if (filters.startDate) {
    params.start_date = filters.startDate;
  }

  if (filters.endDate) {
    params.end_date = filters.endDate;
  }

  if (branchId && branchId !== "all") {
    params.branch_id = branchId;
  }

  if (filters.planId) {
    params.plan_id = filters.planId;
  }

  const search = String(filters.search || "").trim();
  if (search) {
    params.search = search;
  }

  return params;
}

export function normalizeFrozenTerminatedRecord(record, index = 0) {
  const rawStatus = String(record?.status || record?.status_type || "").toLowerCase();
  const isFrozen = rawStatus === "frozen";
  const status = isFrozen ? "frozen" : "terminated";
  const statusLabel =
    record?.status_label || (isFrozen ? "مجمّد" : "ملغى");

  const totalAmount = toFiniteNumber(record?.total_amount);
  const paidAmount = toFiniteNumber(record?.paid_amount);
  const remainingAmount = toFiniteNumber(record?.remaining_amount);

  const eventDate =
    record?.event_date ||
    record?.frozen_at ||
    record?.terminated_at ||
    record?.cancelled_at ||
    record?.updated_at ||
    "-";

  const reason =
    record?.reason ||
    record?.freeze_reason ||
    record?.termination_reason ||
    record?.cancellation_reason ||
    "-";

  const frozenDays = toFiniteNumber(
    record?.frozen_days || record?.freeze_days_count || record?.days_count
  );

  const contactPersons = Array.isArray(record?.contact_persons)
    ? record.contact_persons
    : [];

  const activities = Array.isArray(record?.activities) ? record.activities : [];

  return {
    id: record?.subscription_id || record?.id || `frozen-row-${index}`,
    subscriptionId: record?.subscription_id || record?.id,
    status,
    statusLabel,
    isFrozen,
    memberId: record?.member_id || record?.member?.id,
    memberNumber: record?.member_number || record?.member?.member_number || "-",
    memberName:
      record?.member_name ||
      record?.member?.full_name ||
      record?.member?.person?.full_name ||
      "-",
    memberPhone: getMemberPrimaryPhone(record),
    contactPersons,
    eventDate,
    reason,
    frozenDays,
    freezeStartDate: record?.freeze_start_date || null,
    freezeEndDate: record?.freeze_end_date || null,
    branchName: record?.branch_name || record?.branch?.name || "-",
    planId: record?.plan_id || record?.plan?.id,
    planName: record?.plan_name || record?.plan?.name || "-",
    coachesNames: record?.coaches_names || record?.coach_name || "-",
    activities,
    startDate: record?.start_date || "-",
    endDate: record?.end_date || "-",
    totalAmount,
    paidAmount,
    remainingAmount,
    currency: record?.currency || "SYP",
    currencyType: record?.currency_type || "SYP",
    lostRevenue: toFiniteNumber(record?.lost_revenue || record?.lost_terminated_revenue),
    frozenRevenue: toFiniteNumber(record?.frozen_revenue),
  };
}

export function normalizeFrozenTerminatedResponse(response) {
  const payload =
    response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};

  const summary = {
    ...EMPTY_FROZEN_TERMINATED_SUMMARY,
    ...rawSummary,
  };

  summary.total_records = toFiniteNumber(summary.total_records);
  summary.total_frozen = toFiniteNumber(summary.total_frozen);
  summary.total_terminated = toFiniteNumber(summary.total_terminated);
  summary.total_frozen_revenue = toFiniteNumber(summary.total_frozen_revenue);
  summary.total_lost_terminated_revenue = toFiniteNumber(
    summary.total_lost_terminated_revenue
  );
  summary.currency = String(rawSummary.currency || rawSummary.currency_type || "SYP");
  summary.currency_type = String(rawSummary.currency_type || rawSummary.currency || "SYP");

  const rawRecords = Array.isArray(payload?.records) ? payload.records : [];
  const records = rawRecords.map((record, index) =>
    normalizeFrozenTerminatedRecord(record, index)
  );

  return {
    summary,
    records,
  };
}

export function validateFrozenTerminatedFilters(filters = {}) {
  if (filters.startDate && filters.endDate && filters.startDate > filters.endDate) {
    return "يجب أن يكون تاريخ البداية قبل تاريخ النهاية أو مساوياً له.";
  }
  return "";
}

export function getLookupCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

export function createPrintableFrozenTerminatedReport(rows, summary) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;

  return {
    id: "subscriptions-frozen-terminated",
    title: "تقرير الاشتراكات المجمدة والملغاة",
    description:
      "بيانات الاشتراكات المجمدة والملغاة مع تواريخ وأسباب الإيقاف والإحصائيات المالية المفقودة والمجمدة.",
    metrics: [
      { label: "إجمالي السجلات", value: summary.total_records.toLocaleString("ar") },
      { label: "الاشتراكات المجمدة", value: summary.total_frozen.toLocaleString("ar") },
      { label: "الاشتراكات الملغاة", value: summary.total_terminated.toLocaleString("ar") },
      {
        label: "إجمالي الإيراد المجمد",
        value: formatMoney(summary.total_frozen_revenue, currency),
      },
      {
        label: "إجمالي الإيراد المفقود بالإلغاء",
        value: formatMoney(summary.total_lost_terminated_revenue, currency),
      },
    ],
    columns: [
      { key: "memberNumber", label: "رقم العضوية" },
      { key: "memberName", label: "اللاعب" },
      { key: "memberPhone", label: "الهاتف" },
      { key: "planName", label: "خطة الاشتراك" },
      { key: "statusLabel", label: "الحالة" },
      { key: "eventDate", label: "تاريخ الحدث" },
      { key: "reason", label: "السبب" },
      { key: "totalAmount", label: "القيمة" },
    ],
    rows: rows.map((row) => ({
      ...row,
      eventDate: formatDate(row.eventDate),
      totalAmount: formatMoney(row.totalAmount, currency),
    })),
    emptyMessage: "لا توجد اشتراكات مجمدة أو ملغاة مطابقة للفلاتر.",
  };
}

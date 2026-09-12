import { formatDate, formatMoney } from "@/lib/utils";

export const RENEWAL_STATUS_TYPE_OPTIONS = [
  { value: "all", label: "الكل (الجميع)" },
  { value: "expired_non_renewed", label: "منتهي ولم يجدد" },
  { value: "renewed", label: "تم التجديد" },
];

export const RENEWAL_DATE_FILTER_OPTIONS = [
  { value: "end_date", label: "انقضاء الاشتراك" },
  { value: "start_date", label: "بداية الاشتراك" },
  { value: "created_date", label: "تاريخ القيد" },
];

export const DEFAULT_RENEWAL_REPORT_FILTERS = {
  type: "all",
  dateFilterBy: "end_date",
  startDate: "",
  endDate: "",
  planId: "",
  coachId: "",
  search: "",
};

export const EMPTY_RENEWAL_REPORT_SUMMARY = {
  total_records: 0,
  total_expired_non_renewed: 0,
  total_renewed: 0,
  renewal_rate_percentage: 0,
  total_lost_potential_revenue: 0,
  total_renewed_revenue: 0,
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
  if (Array.isArray(record?.contact_persons) && record.contact_persons.length > 0) {
    const contactWithPhone = record.contact_persons.find((c) => c?.phone_number);
    if (contactWithPhone?.phone_number) {
      return contactWithPhone.phone_number;
    }
  }
  return "-";
}

export function createRenewalReportParams(filters = {}, branchId) {
  const params = {};

  if (filters.type && filters.type !== "all") {
    params.type = filters.type;
  } else if (filters.type === "all") {
    params.type = "all";
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

  if (filters.coachId) {
    params.coach_id = filters.coachId;
  }

  const search = String(filters.search || "").trim();
  if (search) {
    params.search = search;
  }

  return params;
}

export function normalizeRenewalRecord(record, index = 0) {
  const statusType = String(record?.status_type || "expired_non_renewed").toLowerCase();
  const statusLabel =
    record?.status_label || (statusType === "renewed" ? "تم التجديد" : "منتهي ولم يجدد");

  const totalAmount = toFiniteNumber(record?.total_amount);
  const paidAmount = toFiniteNumber(record?.paid_amount);
  const remainingAmount = toFiniteNumber(record?.remaining_amount);
  const daysSinceExpiration = toFiniteNumber(record?.days_since_expiration);

  const absencePeriod = record?.absence_period || {};
  const absenceFormatted =
    absencePeriod?.formatted ||
    (absencePeriod?.last_attendance_date
      ? formatDate(absencePeriod.last_attendance_date)
      : "لم يحضر أبدًا");

  const contactPersons = Array.isArray(record?.contact_persons)
    ? record.contact_persons
    : [];

  const coaches = Array.isArray(record?.coaches) ? record.coaches : [];
  const activities = Array.isArray(record?.activities) ? record.activities : [];

  return {
    id: record?.subscription_id || `renewal-row-${index}`,
    subscriptionId: record?.subscription_id,
    statusType,
    statusLabel,
    memberId: record?.member_id,
    memberNumber: record?.member_number || "-",
    memberName: record?.member_name || "-",
    memberPhone: getMemberPrimaryPhone(record),
    contactPersons,
    absencePeriod,
    absenceFormatted,
    lastAttendanceDate: absencePeriod?.last_attendance_date || null,
    branchName: record?.branch_name || "-",
    planId: record?.plan_id,
    planName: record?.plan_name || "-",
    planType: record?.plan_type || "N/A",
    coaches,
    coachesNames: record?.coaches_names || "لا يوجد مدرب محدد",
    activities,
    startDate: record?.start_date || "-",
    endDate: record?.end_date || "-",
    subscriptionStatus: record?.subscription_status || "active",
    daysSinceExpiration,
    totalAmount,
    paidAmount,
    remainingAmount,
    currency: record?.currency || "SYP",
    currencyType: record?.currency_type || "SYP",
    isFullyPaid: Boolean(record?.is_fully_paid),
    renewalInfo: record?.renewal_info || null,
  };
}

export function normalizeRenewalReportResponse(response) {
  const payload =
    response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};

  const summary = {
    ...EMPTY_RENEWAL_REPORT_SUMMARY,
    ...rawSummary,
  };

  summary.total_records = toFiniteNumber(summary.total_records);
  summary.total_expired_non_renewed = toFiniteNumber(summary.total_expired_non_renewed);
  summary.total_renewed = toFiniteNumber(summary.total_renewed);
  summary.renewal_rate_percentage = toFiniteNumber(summary.renewal_rate_percentage);
  summary.total_lost_potential_revenue = toFiniteNumber(summary.total_lost_potential_revenue);
  summary.total_renewed_revenue = toFiniteNumber(summary.total_renewed_revenue);
  summary.currency = String(rawSummary.currency || rawSummary.currency_type || "SYP");
  summary.currency_type = String(rawSummary.currency_type || rawSummary.currency || "SYP");

  const rawRecords = Array.isArray(payload?.records) ? payload.records : [];
  const records = rawRecords.map((record, index) => normalizeRenewalRecord(record, index));

  return {
    summary,
    records,
  };
}

export function validateRenewalReportFilters(filters = {}) {
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

export function createPrintableRenewalReport(rows, summary) {
  const currency = summary.currency_type === "SYP" ? "ل.س" : summary.currency_type;

  return {
    id: "subscriptions-renewal-status",
    title: "تقرير حالة تجديد الاشتراكات",
    description:
      "بيانات الاشتراكات المنتهية والمجددة مع فترات الغياب وتفاصيل الخطط والمبالغ المالية.",
    metrics: [
      { label: "إجمالي السجلات", value: summary.total_records.toLocaleString("ar") },
      {
        label: "منتهي ولم يجدد",
        value: summary.total_expired_non_renewed.toLocaleString("ar"),
      },
      { label: "تم التجديد", value: summary.total_renewed.toLocaleString("ar") },
      {
        label: "نسبة التجديد",
        value: `${summary.renewal_rate_percentage.toLocaleString("ar", { maximumFractionDigits: 2 })}%`,
      },
      {
        label: "الإيراد المفقود المحتمل",
        value: formatMoney(summary.total_lost_potential_revenue, currency),
      },
      {
        label: "إيرادات التجديد المحققة",
        value: formatMoney(summary.total_renewed_revenue, currency),
      },
    ],
    columns: [
      { key: "memberNumber", label: "رقم العضوية" },
      { key: "memberName", label: "اللاعب" },
      { key: "memberPhone", label: "الهاتف" },
      { key: "planName", label: "خطة الاشتراك" },
      { key: "coachesNames", label: "المدرب" },
      { key: "absenceFormatted", label: "فترة الغياب" },
      { key: "endDate", label: "تاريخ الانتهاء" },
      { key: "daysSinceExpiration", label: "أيام الانتهاء" },
      { key: "statusLabel", label: "حالة التجديد" },
      { key: "totalAmount", label: "القيمة" },
    ],
    rows: rows.map((row) => ({
      ...row,
      endDate: formatDate(row.endDate),
      daysSinceExpiration: `${row.daysSinceExpiration.toLocaleString("ar")} يوم`,
      totalAmount: formatMoney(row.totalAmount, currency),
    })),
    emptyMessage: "لا توجد سجلات مطابقة للفلاتر المختارة.",
  };
}

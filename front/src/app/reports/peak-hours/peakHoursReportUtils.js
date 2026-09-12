export const PEAK_HOURS_ATTENDABLE_OPTIONS = [
  { value: "all", label: "الأعضاء والموظفون" },
  { value: "member", label: "الأعضاء فقط" },
  { value: "staff", label: "الموظفون فقط" },
];

export const EMPTY_PEAK_HOURS_SUMMARY = {
  busiest_day: "-",
  quietest_day: "-",
  peak_hours_range: "غير متاح",
  off_peak_hours_range: "غير متاح",
  total_attendances_analyzed: 0,
  excluded_holidays_count: 0,
};

function toFiniteNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function normalizeRange(value) {
  const normalized = String(value || "").trim();
  return normalized && normalized.toUpperCase() !== "N/A" ? normalized : "غير متاح";
}

function normalizeHourlyItems(items) {
  if (!Array.isArray(items)) return [];

  return items.map((item, index) => ({
    hour: toFiniteNumber(item?.hour ?? index),
    label: String(item?.label || `${String(item?.hour ?? index).padStart(2, "0")}:00`),
    count: toFiniteNumber(item?.count),
  }));
}

export function createPeakHoursReportParams(filters = {}, branchId) {
  const params = {};

  if (filters.startDate) params.start_date = filters.startDate;
  if (filters.endDate) params.end_date = filters.endDate;
  if (branchId && branchId !== "all") params.branch_id = branchId;
  if (filters.attendableType && filters.attendableType !== "all") {
    params.attendable_type = filters.attendableType;
  }

  return params;
}

export function validatePeakHoursFilters(filters = {}) {
  if (filters.startDate && filters.endDate && filters.startDate > filters.endDate) {
    return "يجب أن يكون تاريخ البداية قبل تاريخ النهاية أو مساوياً له.";
  }

  return "";
}

export function normalizePeakHoursReportResponse(response) {
  const payload = response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};
  const summary = {
    busiest_day: String(rawSummary.busiest_day || "-"),
    quietest_day: String(rawSummary.quietest_day || "-"),
    peak_hours_range: normalizeRange(rawSummary.peak_hours_range),
    off_peak_hours_range: normalizeRange(rawSummary.off_peak_hours_range),
    total_attendances_analyzed: toFiniteNumber(rawSummary.total_attendances_analyzed),
    excluded_holidays_count: toFiniteNumber(rawSummary.excluded_holidays_count),
  };
  const dailyBreakdown = Array.isArray(payload?.daily_breakdown)
    ? payload.daily_breakdown.map((item, index) => ({
        id: item?.day_number ?? index,
        dayNumber: toFiniteNumber(item?.day_number ?? index),
        dayName: String(item?.day_name || "-"),
        totalCheckIns: toFiniteNumber(item?.total_check_ins),
        isWeeklyHoliday: Boolean(item?.is_weekly_holiday),
      }))
    : [];

  return {
    summary,
    topPeakHours: normalizeHourlyItems(payload?.top_peak_hours),
    topOffPeakHours: normalizeHourlyItems(payload?.top_off_peak_hours),
    hourlyBreakdown: normalizeHourlyItems(payload?.hourly_breakdown),
    dailyBreakdown,
    specificHolidays: Array.isArray(payload?.specific_holidays) ? payload.specific_holidays : [],
  };
}

export function getHolidayLabel(holiday) {
  if (typeof holiday === "string") return holiday;
  if (!holiday || typeof holiday !== "object") return "-";

  return (
    [holiday.name || holiday.title || holiday.description, holiday.date]
      .filter(Boolean)
      .join(" — ") || "-"
  );
}

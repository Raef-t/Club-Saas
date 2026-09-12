export const SHIFT_ATTENDANCE_MODE_OPTIONS = [
  { value: "date", label: "يوم محدد (تاريخ يومي)" },
  { value: "month", label: "شهر محدد (تصفية شهرية)" },
  { value: "range", label: "نطاق زمني (بين تاريخين)" },
  { value: "all", label: "بدون تقييد زمني" },
];

export const DEFAULT_SHIFT_ATTENDANCE_FILTERS = {
  mode: "date",
  date: "",
  month: "",
  startDate: "",
  endDate: "",
  activityId: "",
  shiftId: "",
};

export const EMPTY_SHIFT_ATTENDANCE_SUMMARY = {
  period_label: "غير محدد",
  total_shift_attendances: 0,
  total_shifts_count: 0,
  busiest_shift: null,
  quietest_shift: null,
};

export function toFiniteNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

export function formatShiftTime(timeStr) {
  if (!timeStr || typeof timeStr !== "string") return "-";
  const parts = timeStr.split(":");
  if (parts.length < 2) return timeStr;
  let hours = parseInt(parts[0], 10);
  const minutes = parts[1];
  if (Number.isNaN(hours)) return timeStr;

  const isPm = hours >= 12;
  const period = isPm ? "م" : "ص";
  hours = hours % 12 || 12;
  return `${hours}:${minutes} ${period}`;
}

export function createShiftAttendanceParams(filters = {}, branchId) {
  const params = {};

  if (filters.mode === "date" && filters.date) {
    params.date = filters.date;
  } else if (filters.mode === "month" && filters.month) {
    params.month = filters.month;
  } else if (filters.mode === "range") {
    if (filters.startDate) params.start_date = filters.startDate;
    if (filters.endDate) params.end_date = filters.endDate;
  }

  if (branchId && branchId !== "all") {
    params.branch_id = branchId;
  }

  if (filters.activityId) {
    params.activity_id = filters.activityId;
  }

  if (filters.shiftId) {
    params.shift_id = filters.shiftId;
  }

  return params;
}

export function normalizeShiftAttendanceRecord(record, index = 0) {
  const startTime = record?.start_time || "-";
  const endTime = record?.end_time || "-";
  const timeFormatted =
    startTime !== "-" && endTime !== "-"
      ? `${formatShiftTime(startTime)} - ${formatShiftTime(endTime)}`
      : "-";

  return {
    id: record?.shift_id || `shift-row-${index}`,
    shiftId: record?.shift_id,
    shiftName: record?.shift_name || "وردية بدون اسم",
    branchId: record?.branch_id,
    branchName: record?.branch_name || "-",
    startTime,
    endTime,
    timeFormatted,
    attendedPlayersCount: toFiniteNumber(record?.attended_players_count),
    uniquePlayersCount: toFiniteNumber(record?.unique_players_count),
    crowdPercentage: toFiniteNumber(record?.crowd_percentage),
  };
}

export function normalizeShiftAttendanceResponse(response) {
  const payload =
    response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};

  const summary = {
    ...EMPTY_SHIFT_ATTENDANCE_SUMMARY,
    ...rawSummary,
  };

  summary.period_label = String(rawSummary.period_label || "غير محدد");
  summary.total_shift_attendances = toFiniteNumber(rawSummary.total_shift_attendances);
  summary.total_shifts_count = toFiniteNumber(rawSummary.total_shifts_count);
  summary.busiest_shift = rawSummary.busiest_shift || null;
  summary.quietest_shift = rawSummary.quietest_shift || null;

  const rawRecords = Array.isArray(payload?.records) ? payload.records : [];
  const records = rawRecords.map((record, index) =>
    normalizeShiftAttendanceRecord(record, index)
  );

  return {
    summary,
    records,
  };
}

export function validateShiftAttendanceFilters(filters = {}) {
  if (
    filters.mode === "range" &&
    filters.startDate &&
    filters.endDate &&
    filters.startDate > filters.endDate
  ) {
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

export function createPrintableShiftAttendanceReport(rows, summary) {
  return {
    id: "shifts-attendance-crowd",
    title: "تقرير حضور وازدحام ورديات الأنشطة",
    description: `إحصائيات حضور اللاعبين وازدحام الورديات للفترة: ${summary.period_label}`,
    metrics: [
      { label: "الفترة", value: summary.period_label },
      {
        label: "إجمالي مرات الحضور",
        value: summary.total_shift_attendances.toLocaleString("ar"),
      },
      {
        label: "عدد الورديات المشمولة",
        value: summary.total_shifts_count.toLocaleString("ar"),
      },
      {
        label: "الوردية الأكثر ازدحاماً",
        value: summary.busiest_shift?.shift_name || "لا توجد",
      },
      {
        label: "الوردية الأقل ازدحاماً",
        value: summary.quietest_shift?.shift_name || "لا توجد",
      },
    ],
    columns: [
      { key: "shiftName", label: "اسم الوردية" },
      { key: "branchName", label: "الفرع" },
      { key: "timeFormatted", label: "توقيت الوردية" },
      { key: "attendedPlayersCount", label: "مرات الحضور" },
      { key: "uniquePlayersCount", label: "اللاعبين الفريدين" },
      { key: "crowdPercentage", label: "نسبة الازدحام" },
    ],
    rows: rows.map((row) => ({
      ...row,
      attendedPlayersCount: row.attendedPlayersCount.toLocaleString("ar"),
      uniquePlayersCount: row.uniquePlayersCount.toLocaleString("ar"),
      crowdPercentage: `${row.crowdPercentage.toLocaleString("ar", {
        maximumFractionDigits: 1,
      })}%`,
    })),
    emptyMessage: "لا توجد ورديات مطابقة للفلاتر المختارة.",
  };
}

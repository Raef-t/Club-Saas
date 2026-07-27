/**
 * Removes a field error without mutating the current errors object.
 */
export function clearFieldError(setErrors, field) {
  setErrors((current) => {
    if (!current[field]) return current;
    const updated = { ...current };
    delete updated[field];
    return updated;
  });
}

/**
 * Converts a UI time value to the `HH:mm` format expected by the backend.
 */
export function formatTimeForApi(time) {
  return time ? String(time).slice(0, 5) : null;
}

/**
 * Returns the localized branch name from string or translated API values.
 */
export function getBranchName(branch, fallback = "الفرع المختار") {
  if (!branch?.name) return fallback;
  if (typeof branch.name === "string") return branch.name;
  return branch.name.ar || branch.name.en || fallback;
}

/**
 * Extracts a single settings record from supported backend response shapes.
 */
export function getSettingsRecord(response) {
  const nestedRecord = response?.data?.data;

  if (nestedRecord && typeof nestedRecord === "object" && !Array.isArray(nestedRecord)) {
    return nestedRecord;
  }

  const record = response?.data;
  if (record && typeof record === "object" && !Array.isArray(record)) {
    return record;
  }

  if (response && typeof response === "object" && !Array.isArray(response)) {
    return response;
  }

  return null;
}

/**
 * Extracts a list from the response shapes used by settings endpoints.
 */
export function getSettingsCollection(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

/**
 * Creates the editable branch-settings form from an API record.
 */
export function createBranchSettingsForm(settings) {
  return {
    defaultClubCommission: String(settings?.default_club_commission_percentage ?? "40"),
    defaultCoachCommission: String(settings?.default_coach_commission_percentage ?? "60"),
    defaultEmployeeSalary: String(settings?.default_employee_salary ?? "3500"),
    workingHoursStart: formatTimeForApi(settings?.working_hours_start) || "",
    workingHoursEnd: formatTimeForApi(settings?.working_hours_end) || "",
    dailyEntryPrice: String(settings?.daily_entry_price ?? "0"),
    lockerPrice: String(settings?.locker_price ?? "0"),
    allowFreeze: Boolean(settings?.allow_freeze),
    displayMixedActivities: Boolean(settings?.display_mixed_activities),
  };
}

/**
 * Converts the editable branch-settings form to the backend contract.
 */
export function createBranchSettingsPayload(form) {
  return {
    default_club_commission_percentage: Number(form.defaultClubCommission) || 0,
    default_coach_commission_percentage: Number(form.defaultCoachCommission) || 0,
    default_employee_salary: Number(form.defaultEmployeeSalary) || 0,
    working_hours_start: formatTimeForApi(form.workingHoursStart),
    working_hours_end: formatTimeForApi(form.workingHoursEnd),
    daily_entry_price: Number(form.dailyEntryPrice) || 0,
    locker_price: Number(form.lockerPrice) || 0,
    allow_freeze: Boolean(form.allowFreeze),
    display_mixed_activities: Boolean(form.displayMixedActivities),
  };
}

/**
 * Creates a shift form for either a new or existing shift.
 */
export function createShiftForm(shift) {
  return {
    shiftName: shift?.name || "",
    shiftStartTime: formatTimeForApi(shift?.start_time) || "08:00",
    shiftEndTime: formatTimeForApi(shift?.end_time) || "16:00",
    shiftGender: shift?.gender_allowed || "mixed",
  };
}

/**
 * Converts the editable shift form to the backend contract.
 */
export function createShiftPayload(form) {
  return {
    name: form.shiftName.trim(),
    day_of_week: 0,
    start_time: formatTimeForApi(form.shiftStartTime),
    end_time: formatTimeForApi(form.shiftEndTime),
    gender_allowed: form.shiftGender,
  };
}

/**
 * Creates a holiday form for either a new or existing holiday.
 */
export function createHolidayForm(holiday) {
  return {
    holidayType: holiday?.type || "weekly",
    holidayDay: String(holiday?.day_of_week ?? "5"),
    holidayStartDate: holiday?.start_date || "",
    holidayEndDate: holiday?.end_date || "",
  };
}

/**
 * Converts the editable holiday form to the backend contract.
 */
export function createHolidayPayload(form) {
  const isWeekly = form.holidayType === "weekly";

  return {
    type: form.holidayType,
    day_of_week: isWeekly ? Number(form.holidayDay) : null,
    start_date: isWeekly ? null : form.holidayStartDate,
    end_date: isWeekly ? null : form.holidayEndDate,
  };
}

/**
 * Escapes untrusted text before inserting it into a printable HTML document.
 */
export function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

/**
 * Formats the period shown for a weekly or date-range holiday.
 */
export function getHolidayPeriod(holiday, daysMap, locale = "ar-SY") {
  if (holiday?.type === "weekly") {
    return `كل يوم ${daysMap[holiday.day_of_week] || holiday.day_of_week || "-"}`;
  }

  const start = holiday?.start_date ? new Date(holiday.start_date).toLocaleDateString(locale) : "-";
  const end = holiday?.end_date ? new Date(holiday.end_date).toLocaleDateString(locale) : "-";

  return `من ${start} إلى ${end}`;
}

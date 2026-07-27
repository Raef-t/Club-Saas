import { formatLocalizedName } from "../../../lib/utils";

export const EMPTY_ACTIVITY_FORM = {
  name: "",
  description: "",
  gender_allowed: "mixed",
  branch_id: "",
  activity_type_id: "",
  is_active: true,
  shifts: [],
};

/**
 * Extracts an activity list from supported backend response shapes.
 */
export function getActivityCollection(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

/**
 * Extracts one activity from supported backend response shapes.
 */
export function getActivityRecord(response) {
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
 * Returns the localized display name of an activity.
 */
export function getActivityName(activity) {
  return formatLocalizedName(activity?.name);
}

/**
 * Filters activities by localized name or description.
 */
export function filterActivities(activities, search) {
  const normalizedSearch = search.trim().toLowerCase();
  if (!normalizedSearch) return activities;

  return activities.filter((activity) =>
    [getActivityName(activity), activity.description]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(normalizedSearch)),
  );
}

/**
 * Creates the activity statistics displayed above the table.
 */
export function createActivityStats(activities) {
  const activeCount = activities.filter((activity) => activity.is_active !== false).length;

  return [
    {
      title: "إجمالي الأنشطة",
      value: activities.length.toLocaleString("ar"),
      helper: "الأنشطة الرياضية المسجلة",
      tone: "yellow",
      compact: true,
    },
    {
      title: "أنشطة نشطة",
      value: activeCount.toLocaleString("ar"),
      helper: "المتاحة للحجز حالياً",
      tone: "green",
      compact: true,
    },
    {
      title: "أنشطة غير نشطة",
      value: (activities.length - activeCount).toLocaleString("ar"),
      helper: "الأنشطة الموقوفة مؤقتاً",
      tone: "default",
      compact: true,
    },
  ];
}

/**
 * Converts an activity record to values understood by the editor form.
 */
export function createActivityFormValues(activity) {
  if (!activity) return { ...EMPTY_ACTIVITY_FORM };

  return {
    name:
      typeof activity.name === "object"
        ? activity.name?.ar || activity.name?.en || ""
        : activity.name || "",
    description: activity.description || "",
    gender_allowed: activity.gender_allowed || "mixed",
    branch_id: activity.branch_id
      ? String(activity.branch_id)
      : activity.branch?.id
        ? String(activity.branch.id)
        : "",
    activity_type_id: activity.activity_type_id
      ? String(activity.activity_type_id)
      : activity.activity_type?.id
        ? String(activity.activity_type.id)
        : "",
    is_active: activity.is_active !== false,
    shifts: Array.isArray(activity.shifts)
      ? activity.shifts.map((shift) => Number(typeof shift === "object" ? shift.id : shift))
      : [],
  };
}

/**
 * Converts validated editor values to the backend activity contract.
 */
export function createActivityPayload(form, includeShifts) {
  return {
    name: form.name.trim(),
    description: form.description.trim() || null,
    gender_allowed: form.gender_allowed || "mixed",
    branch_id: Number(form.branch_id),
    activity_type_id: Number(form.activity_type_id),
    is_active: Boolean(form.is_active),
    shifts: includeShifts ? form.shifts.map(Number) : [],
  };
}

/**
 * Creates localized dropdown options from records with translated names.
 */
export function createActivityOptions(items) {
  return items.map((item) => ({
    value: String(item.id),
    label: formatLocalizedName(item.name),
  }));
}

import { formatLocalizedName } from "@/lib/utils";

export const TIME_CAPACITY_DAY_OPTIONS = [
  { value: "all", label: "كل أيام الأسبوع" },
  { value: "0", label: "الأحد" },
  { value: "1", label: "الاثنين" },
  { value: "2", label: "الثلاثاء" },
  { value: "3", label: "الأربعاء" },
  { value: "4", label: "الخميس" },
  { value: "5", label: "الجمعة" },
  { value: "6", label: "السبت" },
];

export const EMPTY_TIME_CAPACITY_SUMMARY = {
  total_activities: 0,
  total_coaches: 0,
  total_plans: 0,
  total_active_subscribers: 0,
};

const DAY_LABELS = Object.fromEntries(
  TIME_CAPACITY_DAY_OPTIONS.filter((option) => option.value !== "all").map((option) => [
    option.value,
    option.label,
  ]),
);

function firstValue(...values) {
  return values.find((value) => value !== undefined && value !== null && value !== "");
}

function firstCollection(...values) {
  return values.find((value) => Array.isArray(value) && value.length > 0) || [];
}

function toFiniteNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function displayName(value, fallback = "-") {
  const name = formatLocalizedName(value);
  return name === "-" ? fallback : name;
}

function normalizeTimeValue(value) {
  const match = String(value || "").match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);
  if (!match) return "";
  return `${match[1].padStart(2, "0")}:${match[2]}:${match[3] || "00"}`;
}

function getCoachName(coach = {}) {
  const person = coach?.person || {};
  const splitName = `${firstValue(coach?.first_name, person?.first_name, "")} ${firstValue(
    coach?.last_name,
    person?.last_name,
    "",
  )}`.trim();

  return firstValue(
    coach?.coach_name,
    coach?.full_name,
    person?.full_name,
    splitName,
    displayName(coach?.name),
  );
}

function getSlots(entity = {}) {
  return firstCollection(
    entity?.time_slots,
    entity?.slots,
    entity?.sessions,
    entity?.schedules,
    entity?.time_ranges,
  );
}

function createCapacityRow(activity, coach, plan, slot, index) {
  const activityRecord = activity?.activity || activity || {};
  const coachRecord = coach?.coach || coach || {};
  const planRecord = plan?.plan || plan || {};
  const source = slot || plan || coach || activity || {};
  const capacity = toFiniteNumber(
    firstValue(
      source?.capacity,
      source?.max_capacity,
      source?.total_capacity,
      planRecord?.capacity,
      activityRecord?.capacity,
    ),
  );
  const activeSubscribers = toFiniteNumber(
    firstValue(
      source?.active_subscribers,
      source?.active_subscribers_count,
      source?.current_subscribers,
      source?.subscribers_count,
      source?.total_active_subscribers,
      planRecord?.total_active_subscribers,
    ),
  );
  const explicitRemaining = firstValue(
    source?.remaining_capacity,
    source?.available_capacity,
    source?.available_slots,
  );
  const remainingCapacity =
    explicitRemaining === undefined
      ? Math.max(0, capacity - activeSubscribers)
      : toFiniteNumber(explicitRemaining);
  const dayValue = String(
    firstValue(
      source?.day_of_week,
      source?.weekday,
      planRecord?.day_of_week,
      coachRecord?.day_of_week,
      activityRecord?.day_of_week,
      "",
    ),
  );
  const utilization =
    capacity > 0 ? Math.min(100, Math.round((activeSubscribers / capacity) * 100)) : 0;

  return {
    id: [
      activityRecord?.id || activity?.activity_id || "activity",
      coachRecord?.id || coach?.coach_id || "coach",
      planRecord?.id || plan?.plan_id || "plan",
      source?.id || source?.slot_id || index,
    ].join("-"),
    activityName: firstValue(
      activity?.activity_name,
      source?.activity_name,
      displayName(activityRecord?.name),
    ),
    coachName: getCoachName(coachRecord),
    planName: firstValue(plan?.plan_name, source?.plan_name, displayName(planRecord?.name)),
    dayOfWeek:
      DAY_LABELS[dayValue] || firstValue(source?.day_name, source?.weekday_name, dayValue, "-"),
    startTime: firstValue(source?.start_time, source?.starts_at, planRecord?.start_time, "-"),
    endTime: firstValue(source?.end_time, source?.ends_at, planRecord?.end_time, "-"),
    capacity,
    activeSubscribers,
    remainingCapacity,
    utilization,
    capacityStatus: capacity > 0 && remainingCapacity <= 0 ? "full" : "available",
    capacityStatusLabel: capacity > 0 && remainingCapacity <= 0 ? "ممتلئة" : "متاحة",
  };
}

export function getTimeCapacityLookupCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

export function createTimeCapacityReportParams(filters = {}, branchId) {
  const params = {};
  const startTime = normalizeTimeValue(filters.startTime);
  const endTime = normalizeTimeValue(filters.endTime);

  if (startTime) params.start_time = startTime;
  if (endTime) params.end_time = endTime;
  if (filters.dayOfWeek !== "" && filters.dayOfWeek !== "all" && filters.dayOfWeek != null) {
    params.day_of_week = filters.dayOfWeek;
  }
  if (branchId && branchId !== "all") params.branch_id = branchId;
  if (filters.planId) params.plan_id = filters.planId;
  if (filters.activityId) params.activity_id = filters.activityId;

  return params;
}

export function validateTimeCapacityFilters(filters = {}) {
  const startTime = normalizeTimeValue(filters.startTime);
  const endTime = normalizeTimeValue(filters.endTime);

  if (filters.startTime && !startTime) return "صيغة وقت البداية غير صحيحة.";
  if (filters.endTime && !endTime) return "صيغة وقت النهاية غير صحيحة.";
  if (startTime && endTime && startTime >= endTime) {
    return "يجب أن يكون وقت البداية قبل وقت النهاية.";
  }

  return "";
}

export function normalizeTimeCapacityReportResponse(response) {
  const payload = response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};
  const summary = { ...EMPTY_TIME_CAPACITY_SUMMARY };

  Object.keys(summary).forEach((key) => {
    summary[key] = toFiniteNumber(rawSummary[key]);
  });

  return {
    summary,
    activities: Array.isArray(payload?.activities) ? payload.activities : [],
  };
}

export function flattenTimeCapacityActivities(activities = []) {
  const rows = [];

  activities.forEach((activity) => {
    const coaches = firstCollection(activity?.coaches, activity?.trainers);
    const activityPlans = firstCollection(activity?.plans, activity?.subscription_plans);

    if (coaches.length > 0) {
      coaches.forEach((coach) => {
        const coachPlans = firstCollection(coach?.plans, coach?.subscription_plans);

        if (coachPlans.length > 0) {
          coachPlans.forEach((plan) => {
            const slots = getSlots(plan);
            if (slots.length > 0) {
              slots.forEach((slot) =>
                rows.push(createCapacityRow(activity, coach, plan, slot, rows.length)),
              );
            } else {
              rows.push(createCapacityRow(activity, coach, plan, null, rows.length));
            }
          });
          return;
        }

        const slots = getSlots(coach);
        if (slots.length > 0) {
          slots.forEach((slot) =>
            rows.push(createCapacityRow(activity, coach, null, slot, rows.length)),
          );
        } else {
          rows.push(createCapacityRow(activity, coach, null, null, rows.length));
        }
      });
      return;
    }

    if (activityPlans.length > 0) {
      activityPlans.forEach((plan) => {
        const slots = getSlots(plan);
        if (slots.length > 0) {
          slots.forEach((slot) =>
            rows.push(createCapacityRow(activity, null, plan, slot, rows.length)),
          );
        } else {
          rows.push(createCapacityRow(activity, null, plan, null, rows.length));
        }
      });
      return;
    }

    const slots = getSlots(activity);
    if (slots.length > 0) {
      slots.forEach((slot) =>
        rows.push(createCapacityRow(activity, null, null, slot, rows.length)),
      );
    } else {
      rows.push(createCapacityRow(activity, null, null, null, rows.length));
    }
  });

  return rows;
}

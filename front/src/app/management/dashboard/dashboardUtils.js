import { SCHEDULE_DAYS } from "../schedule/scheduleConstants";
import { getEntityBranchIds } from "../../../lib/managementBranchUtils";
import { formatLocalizedName } from "../../../lib/utils";

const SUBSCRIPTION_COLORS = ["#16e79b", "#fccd03", "#f2f2f2", "#0755ff", "#7925ff"];

/**
 * Extracts a collection from the response shapes returned by the backend.
 */
export function getDashboardCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

/**
 * Extracts the weekday map from the schedule response.
 */
function getSchedulePayload(response) {
  const payload = response?.data?.data || response?.data || response;
  return payload && typeof payload === "object" && !Array.isArray(payload) ? payload : {};
}

/**
 * Checks whether a schedule session belongs to the selected branch.
 */
function belongsToBranch(session, selectedBranchId) {
  if (!selectedBranchId || selectedBranchId === "all") return true;

  const branchIds = getEntityBranchIds(session);
  return branchIds.length === 0 || branchIds.includes(String(selectedBranchId));
}

/**
 * Returns a display value without exposing the generic localized-name placeholder.
 */
function getDisplayName(value, fallback) {
  const name = formatLocalizedName(value);
  return name === "-" ? fallback : name;
}

/**
 * Converts a backend time into minutes from the beginning of the day.
 */
function getTimeInMinutes(value) {
  const [hours, minutes] = String(value || "")
    .slice(0, 5)
    .split(":")
    .map(Number);

  if (!Number.isFinite(hours) || !Number.isFinite(minutes)) return null;
  return hours * 60 + minutes;
}

/**
 * Resolves the current visual status of a recurring daily session.
 */
function getSessionStatus(session, now) {
  const start = getTimeInMinutes(session.start_time);
  const rawEnd = getTimeInMinutes(session.end_time);

  if (start === null || rawEnd === null) {
    return { label: "مجدولة", tone: "neutral" };
  }

  const current = now.getHours() * 60 + now.getMinutes();
  const end = rawEnd <= start ? rawEnd + 24 * 60 : rawEnd;
  const adjustedCurrent = end > 24 * 60 && current < start ? current + 24 * 60 : current;

  if (adjustedCurrent < start) return { label: "قادمة", tone: "yellow" };
  if (adjustedCurrent <= end) return { label: "جارية", tone: "green" };
  return { label: "منتهية", tone: "neutral" };
}

/**
 * Converts today's backend schedule into rows for the dashboard table.
 */
export function createTodaySchedule(response, selectedBranchId = "all", now = new Date()) {
  const day = SCHEDULE_DAYS[now.getDay()];
  const sessions = getSchedulePayload(response)[day?.apiKey];

  if (!Array.isArray(sessions)) return [];

  return sessions
    .filter((session) => belongsToBranch(session, selectedBranchId))
    .map((session, index) => ({
      id: session.id || `${day.apiKey}-${session.start_time || index}-${index}`,
      title: getDisplayName(
        session.plan_name || session.plan?.name || session.activity_name || session.activity?.name,
        "حصة مجدولة",
      ),
      coach:
        session.coach?.person?.full_name ||
        session.coach?.name ||
        session.coach_name ||
        "لم يحدد المدرب",
      branch: getDisplayName(session.branch?.name || session.branch_name, "الفرع المحدد"),
      startTime: String(session.start_time || "-").slice(0, 5),
      endTime: String(session.end_time || "-").slice(0, 5),
      status: getSessionStatus(session, now),
    }))
    .sort((first, second) => first.startTime.localeCompare(second.startTime));
}

/**
 * Creates the weekly scheduled-session chart from backend schedule data.
 */
export function createWeeklyScheduleChart(response, selectedBranchId = "all") {
  const payload = getSchedulePayload(response);

  return {
    labels: SCHEDULE_DAYS.map((day) => day.label.replace(/^ال/, "")),
    yellow: SCHEDULE_DAYS.map((day) => {
      const sessions = Array.isArray(payload[day.apiKey]) ? payload[day.apiKey] : [];
      return sessions.filter((session) => belongsToBranch(session, selectedBranchId)).length;
    }),
  };
}

/**
 * Groups active subscriptions into the plan distribution used by the donut.
 */
export function createSubscriptionMix(subscriptions) {
  const activeSubscriptions = subscriptions.filter(
    (subscription) =>
      subscription.status === "active" ||
      (!subscription.status && subscription.is_active !== false),
  );
  const source = activeSubscriptions.length ? activeSubscriptions : subscriptions;
  const totals = new Map();

  source.forEach((subscription) => {
    const label = getDisplayName(
      subscription.plan?.name || subscription.subscription_plan?.name || subscription.plan_name,
      "خطة أخرى",
    );
    totals.set(label, (totals.get(label) || 0) + 1);
  });

  return [...totals.entries()]
    .sort((first, second) => second[1] - first[1])
    .slice(0, SUBSCRIPTION_COLORS.length)
    .map(([label, value], index) => ({
      label,
      value,
      color: SUBSCRIPTION_COLORS[index],
    }));
}

/**
 * Creates live management statistics and links each card to its source page.
 */
export function createDashboardStats({
  members,
  coaches,
  subscriptions,
  todaySessions,
  now = new Date(),
}) {
  const activeSubscriptions = subscriptions.filter(
    (subscription) => subscription.status === "active",
  );
  const today = new Date(now);
  today.setHours(0, 0, 0, 0);
  const sevenDaysLater = new Date(today);
  sevenDaysLater.setDate(sevenDaysLater.getDate() + 7);
  const expiringSoon = subscriptions.filter((subscription) => {
    const endDate = new Date(subscription.end_date);
    return (
      !Number.isNaN(endDate.getTime()) &&
      endDate >= today &&
      endDate <= sevenDaysLater &&
      subscription.status !== "cancelled"
    );
  }).length;

  return [
    {
      title: "إجمالي الأعضاء",
      value: members.length.toLocaleString("ar"),
      helper: "الأعضاء المسجلون",
      tone: "yellow",
      iconKey: "members",
      compact: true,
      href: "/management/members",
    },
    {
      title: "المدربون",
      value: coaches.length.toLocaleString("ar"),
      helper: "المدربون المسجلون",
      tone: "cyan",
      iconKey: "coaches",
      compact: true,
      href: "/management/coaches",
    },
    {
      title: "اشتراكات نشطة",
      value: activeSubscriptions.length.toLocaleString("ar"),
      helper: "الاشتراكات الفعالة",
      tone: "green",
      iconKey: "subscriptions",
      compact: true,
      href: "/management/subscriptions",
    },
    {
      title: "تنتهي قريباً",
      value: expiringSoon.toLocaleString("ar"),
      helper: "خلال سبعة أيام",
      tone: "orange",
      iconKey: "expiring",
      compact: true,
      href: "/management/subscriptions",
    },
    {
      title: "حصص اليوم",
      value: todaySessions.length.toLocaleString("ar"),
      helper: "حسب جدول الدوام",
      tone: "blue",
      iconKey: "schedule",
      compact: true,
      href: "/management/schedule",
    },
  ];
}

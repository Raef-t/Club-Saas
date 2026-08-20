import { SCHEDULE_DAYS } from "../schedule/scheduleConstants";
import { getEntityBranchIds } from "../../../lib/managementBranchUtils";
import { formatLocalizedName } from "../../../lib/utils";

const COACH_SUBSCRIPTION_COLORS = [
  "#16e79b",
  "#f2dc2e",
  "#38bdf8",
  "#fb7185",
  "#a78bfa",
  "#f97316",
  "#2dd4bf",
  "#e879f9",
  "#84cc16",
  "#60a5fa",
];

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
 * Finds the live API record that belongs to one scheduled session.
 */
function getActiveSessionRecord(session, activeSessions) {
  if (!Array.isArray(activeSessions)) return null;

  const sessionTemplateId = session.id ?? session.session_template_id;
  const exactMatch = activeSessions.find(
    (activeSession) =>
      sessionTemplateId != null &&
      activeSession.session_template_id != null &&
      String(activeSession.session_template_id) === String(sessionTemplateId),
  );

  if (exactMatch) return exactMatch;

  const planId = session.plan_id ?? session.plan?.id;
  const startTime = String(session.start_time || "").slice(0, 5);
  const endTime = String(session.end_time || "").slice(0, 5);

  if (planId == null || !startTime || !endTime) return null;

  return (
    activeSessions.find((activeSession) => {
      const activePlanId = activeSession.plan_id ?? activeSession.plan?.id;
      return (
        activePlanId != null &&
        String(activePlanId) === String(planId) &&
        String(activeSession.start_time || "").slice(0, 5) === startTime &&
        String(activeSession.end_time || "").slice(0, 5) === endTime
      );
    }) || null
  );
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
export function createTodaySchedule(
  response,
  selectedBranchId = "all",
  now = new Date(),
  activeSessions = null,
) {
  const day = SCHEDULE_DAYS.find((scheduleDay) => scheduleDay.dayIndex === now.getDay());
  const sessions = getSchedulePayload(response)[day?.apiKey];

  if (!Array.isArray(sessions)) return [];

  return sessions
    .filter((session) => belongsToBranch(session, selectedBranchId))
    .map((session, index) => {
      const activeSession = getActiveSessionRecord(session, activeSessions);
      const presentPlayersCount = Array.isArray(activeSessions)
        ? Math.max(0, Number(activeSession?.present_players_count) || 0)
        : null;

      return {
        id: session.id || `${day.apiKey}-${session.start_time || index}-${index}`,
        title: getDisplayName(
          session.plan_name ||
            session.plan?.name ||
            session.activity_name ||
            session.activity?.name,
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
        presentPlayersCount,
        status: getSessionStatus(session, now),
      };
    })
    .sort((first, second) => first.startTime.localeCompare(second.startTime));
}

/**
 * Creates the shift attendance bar chart data from the new report API.
 * Sorts by attendance and returns the top 7 busiest shifts.
 */
export function createShiftAttendanceChart(response) {
  const payload = response?.data?.records || response?.data || response || [];
  const records = Array.isArray(payload) ? payload : [];

  // Aggregate data by day + shift to combine branches
  const grouped = new Map();
  records.forEach((record) => {
    if (!record) return;
    const day = record.day_name || record.day;
    const shift = record.shift_name || record.shift || record.name;
    const key = day && shift ? `${day} - ${shift}` : shift || day || "غير محدد";
    const count = Number(record.attended_players_count ?? record.count ?? 0) || 0;
    grouped.set(key, (grouped.get(key) || 0) + count);
  });

  const aggregatedData = Array.from(grouped.entries()).map(([label, value]) => ({
    label,
    value,
  }));

  const sortedData = aggregatedData.sort((a, b) => b.value - a.value);

  return sortedData.slice(0, 7);
}

/**
 * Converts the coaches subscriptions report into interactive donut data.
 */
export function createCoachSubscriptionMix(response) {
  const payload = response?.data?.data || response?.data || response;
  const coaches = Array.isArray(payload?.group_session_coaches)
    ? payload.group_session_coaches
    : [];

  return coaches.map((coach, index) => ({
    id: "coach-" + (coach.coach_id ?? index),
    label: String(coach.coach_name || "كوتش غير محدد"),
    value: Math.max(0, Number(coach.active_players_count) || 0),
    activities: Array.isArray(coach.activities) ? coach.activities.filter(Boolean) : [],
    color: COACH_SUBSCRIPTION_COLORS[index % COACH_SUBSCRIPTION_COLORS.length],
  }));
}

/**
 * Creates live management statistics and links each card to its source page.
 */
export function createDashboardStats({ sseStats }) {
  if (!sseStats) return [];

  return [
    {
      title: "المشتركون النشطون",
      value: (sseStats.total_active_subscribed_members ?? 0).toLocaleString("ar"),
      helper: "اشتراكات نشطة حالياً",
      tone: "yellow",
      iconKey: "members",
      compact: true,
      href: "/management/subscriptions?status=active",
    },
    {
      title: "اللاعبون في التدريب",
      value: (sseStats.realtime_training_players_count ?? 0).toLocaleString("ar"),
      helper: "يتدربون حالياً (عام / خاص)",
      tone: "cyan",
      iconKey: "coaches",
      compact: true,
      href: "/management/attendance?status=checked_in",
    },
    {
      title: "اشتراكات تقترب من الانتهاء",
      value: (sseStats.expiring_subscriptions_count ?? 0).toLocaleString("ar"),
      helper: "تنتهي قريباً",
      tone: "orange",
      iconKey: "expiring",
      compact: true,
      href: "/management/subscriptions?status=expiring_soon",
    },
    {
      title: "خزائن مسندة مجاناً",
      value: (sseStats.free_assigned_player_lockers_count ?? 0).toLocaleString("ar"),
      helper: "خزائن مجانية للاعبين",
      tone: "green",
      iconKey: "subscriptions",
      compact: true,
      href: "/management/lockers?status=assigned_free",
    },
    {
      title: "الفعاليات الجارية الآن",
      value: (sseStats.current_active_session_plans?.length ?? 0).toLocaleString("ar"),
      helper: "أنشطة وحصص نشطة",
      tone: "purple",
      iconKey: "schedule",
      compact: true,
      href: "/management/schedule",
    },
  ];
}

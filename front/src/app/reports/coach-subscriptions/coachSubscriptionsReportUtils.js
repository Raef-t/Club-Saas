export const EMPTY_COACH_SUBSCRIPTIONS_SUMMARY = {
  total_group_coaches: 0,
  total_group_active_players: 0,
  general_equipment_active_players: 0,
};

function toFiniteNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

function normalizeActivities(activities) {
  if (!Array.isArray(activities)) return [];

  return activities.map((activity) => String(activity || "").trim()).filter(Boolean);
}

export function createCoachSubscriptionsReportParams(branchId) {
  if (!branchId || branchId === "all") return {};
  return { branch_id: String(branchId) };
}

export function normalizeCoachSubscriptionsReportResponse(response) {
  const payload = response?.data && !Array.isArray(response.data) ? response.data : response || {};
  const rawSummary = payload?.summary || {};
  const groupSessionCoaches = Array.isArray(payload?.group_session_coaches)
    ? payload.group_session_coaches.map((coach, index) => {
        const activities = normalizeActivities(coach?.activities);

        return {
          id: `coach-${coach?.coach_id ?? index}`,
          coachId: coach?.coach_id ?? null,
          coachName: String(coach?.coach_name || "كوتش غير محدد"),
          activities,
          activitiesLabel: activities.join("، ") || "-",
          activitiesCount: activities.length,
          activePlayersCount: toFiniteNumber(coach?.active_players_count),
        };
      })
    : [];
  const rawGeneralEquipment = payload?.general_equipment || {};

  return {
    summary: {
      total_group_coaches: toFiniteNumber(rawSummary.total_group_coaches),
      total_group_active_players: toFiniteNumber(rawSummary.total_group_active_players),
      general_equipment_active_players: toFiniteNumber(rawSummary.general_equipment_active_players),
    },
    groupSessionCoaches,
    generalEquipment: {
      title: String(rawGeneralEquipment.title || "أجهزة عامة"),
      activityTypeName: String(rawGeneralEquipment.activity_type_name || "تدريب عام"),
      activePlayersCount: toFiniteNumber(rawGeneralEquipment.active_players_count),
    },
  };
}

import { describe, expect, it } from "vitest";
import {
  createDashboardStats,
  createShiftAttendanceChart,
  createSubscriptionMix,
  createTodaySchedule,
  getDashboardCollection,
} from "./dashboardUtils";

const scheduleResponse = {
  data: {
    Sunday: [
      {
        id: 1,
        branch_id: 3,
        start_time: "10:00:00",
        end_time: "11:00:00",
        plan_name: { ar: "كمال أجسام" },
        coach_name: "أحمد",
      },
      {
        id: 2,
        branch_id: 4,
        start_time: "12:00:00",
        end_time: "13:00:00",
        plan_name: "يوغا",
      },
    ],
    Monday: [{ id: 3, branch_id: 3, start_time: "09:00:00", end_time: "10:00:00" }],
  },
};

describe("management dashboard utilities", () => {
  it("extracts supported backend collection shapes", () => {
    expect(getDashboardCollection({ data: { data: [{ id: 1 }] } })).toEqual([{ id: 1 }]);
    expect(getDashboardCollection({ data: [{ id: 2 }] })).toEqual([{ id: 2 }]);
  });

  it("creates today's branch-filtered schedule rows", () => {
    const sundayMorning = new Date(2026, 6, 26, 9, 30);
    const sessions = createTodaySchedule(scheduleResponse, "3", sundayMorning);

    expect(sessions).toHaveLength(1);
    expect(sessions[0]).toMatchObject({
      id: 1,
      title: "كمال أجسام",
      coach: "أحمد",
      startTime: "10:00",
      status: { label: "قادمة", tone: "yellow" },
    });
  });

  it("creates shift attendance chart data sorted by attendance", () => {
    const reportResponse = {
      data: {
        records: [
          { day_name: "الأحد", shift_name: "الوردية الأولى", attended_players_count: 5 },
          { day_name: "الاثنين", shift_name: "الوردية الثانية", attended_players_count: 12 },
        ],
      },
    };
    const chartData = createShiftAttendanceChart(reportResponse);
    expect(chartData[0]).toEqual({ label: "الاثنين - الوردية الثانية", value: 12 });
  });

  it("groups subscriptions by plan name", () => {
    expect(
      createSubscriptionMix([
        { status: "active", plan: { name: { ar: "شهري" } } },
        { status: "active", plan: { name: { ar: "شهري" } } },
        { status: "active", plan_name: "سنوي" },
      ]).map(({ label, value }) => ({ label, value })),
    ).toEqual([
      { label: "شهري", value: 2 },
      { label: "سنوي", value: 1 },
    ]);
  });

  it("creates linked live statistics from SSE stream data", () => {
    const sseStats = {
      total_active_subscribed_members: 145,
      realtime_training_players_count: 12,
      expiring_subscriptions_count: 5,
      free_assigned_player_lockers_count: 8,
      current_active_session_plans: [{ plan_id: 3 }],
    };

    const stats = createDashboardStats({
      members: [],
      coaches: [],
      subscriptions: [],
      todaySessions: [],
      sseStats,
    });

    expect(stats.map((stat) => stat.value)).toEqual(["145", "12", "5", "8", "1"]);
    expect(stats.map((stat) => stat.iconKey)).toEqual([
      "members",
      "coaches",
      "expiring",
      "subscriptions",
      "schedule",
    ]);
    expect(stats.every((stat) => stat.href.startsWith("/management/"))).toBe(true);
  });
});

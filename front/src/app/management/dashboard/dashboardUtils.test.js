import { describe, expect, it } from "vitest";
import {
  createCoachSubscriptionMix,
  createDashboardStats,
  createShiftAttendanceChart,
  createTodaySchedule,
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
    Tuesday: [{ id: 4, branch_id: 3, start_time: "14:00:00", end_time: "15:00:00" }],
  },
};

describe("management dashboard utilities", () => {
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

  it("adds the live API attendance count to its scheduled session", () => {
    const sundayMorning = new Date(2026, 6, 26, 10, 30);
    const sessions = createTodaySchedule(scheduleResponse, "3", sundayMorning, [
      {
        session_template_id: 1,
        present_players_count: 6,
      },
    ]);

    expect(sessions[0]).toMatchObject({
      id: 1,
      startTime: "10:00",
      endTime: "11:00",
      presentPlayersCount: 6,
    });
  });

  it("keeps attendance pending until the live API responds", () => {
    const sundayMorning = new Date(2026, 6, 26, 10, 30);

    expect(createTodaySchedule(scheduleResponse, "3", sundayMorning)[0]).toMatchObject({
      presentPlayersCount: null,
    });
  });

  it("selects Tuesday by calendar day instead of the displayed array position", () => {
    const tuesdayAfternoon = new Date(2026, 6, 28, 13, 30);

    expect(createTodaySchedule(scheduleResponse, "3", tuesdayAfternoon)).toMatchObject([
      { id: 4, startTime: "14:00" },
    ]);
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

  it("handles shift attendance records without day_name without producing undefined", () => {
    const reportResponse = {
      data: {
        records: [
          { shift_name: "شيفت صباحي", attended_players_count: 20 },
          { shift_name: "شيفت مسائي", attended_players_count: 35 },
        ],
      },
    };
    const chartData = createShiftAttendanceChart(reportResponse);
    expect(chartData[0]).toEqual({ label: "شيفت مسائي", value: 35 });
    expect(chartData[1]).toEqual({ label: "شيفت صباحي", value: 20 });
    expect(chartData[0].label).not.toContain("undefined");
  });

  it("maps the coaches subscriptions report into distinct chart items", () => {
    const items = createCoachSubscriptionMix({
      status: "success",
      data: {
        group_session_coaches: [
          {
            coach_id: 44,
            coach_name: "Coach One",
            activities: ["Mix"],
            active_players_count: 13,
          },
          {
            coach_id: 42,
            coach_name: "Coach Two",
            activities: ["Aerobics"],
            active_players_count: 0,
          },
        ],
      },
    });

    expect(items).toHaveLength(2);
    expect(items[0]).toMatchObject({
      id: "coach-44",
      label: "Coach One",
      value: 13,
      activities: ["Mix"],
    });
    expect(items[1]).toMatchObject({
      id: "coach-42",
      label: "Coach Two",
      value: 0,
    });
    expect(items[0].color).not.toBe(items[1].color);
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
    expect(stats.map((stat) => stat.href)).toEqual([
      "/management/subscriptions?status=active",
      "/management/attendance?status=checked_in",
      "/management/subscriptions?status=expiring_soon",
      "/management/lockers?status=with_member",
      "/management/schedule",
    ]);
  });

  it("does not calculate statistics before the API stream responds", () => {
    expect(
      createDashboardStats({
        members: [{ id: 1 }],
        coaches: [{ id: 2 }],
        subscriptions: [{ id: 3, status: "active" }],
        todaySessions: [{ id: 4 }],
        sseStats: null,
      }),
    ).toEqual([]);
  });

  it("keeps zero values returned by the API stream", () => {
    const stats = createDashboardStats({
      members: [{ id: 1 }],
      coaches: Array.from({ length: 9 }, (_, id) => ({ id })),
      subscriptions: Array.from({ length: 12 }, (_, id) => ({ id, status: "active" })),
      todaySessions: Array.from({ length: 5 }, (_, id) => ({ id })),
      sseStats: {
        total_active_subscribed_members: 10,
        realtime_training_players_count: 0,
        expiring_subscriptions_count: 0,
        free_assigned_player_lockers_count: 0,
        current_active_session_plans: [],
      },
    });

    expect(stats.map((stat) => stat.value)).toEqual(["10", "0", "0", "0", "0"]);
  });
});

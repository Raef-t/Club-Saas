import { describe, expect, it } from "vitest";
import {
  createDashboardStats,
  createSubscriptionMix,
  createTodaySchedule,
  createWeeklyScheduleChart,
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

  it("counts scheduled sessions per weekday and branch", () => {
    expect(createWeeklyScheduleChart(scheduleResponse, "3").yellow).toEqual([1, 1, 0, 0, 0, 0, 0]);
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

  it("creates linked live statistics", () => {
    const stats = createDashboardStats({
      members: [{ id: 1 }, { id: 2 }],
      coaches: [{ id: 1 }],
      subscriptions: [
        { status: "active", end_date: "2026-07-30" },
        { status: "cancelled", end_date: "2026-07-29" },
      ],
      todaySessions: [{ id: 1 }],
      now: new Date(2026, 6, 27),
    });

    expect(stats.map((stat) => stat.value)).toEqual(["2", "1", "1", "1", "1"]);
    expect(stats.map((stat) => stat.iconKey)).toEqual([
      "members",
      "coaches",
      "subscriptions",
      "expiring",
      "schedule",
    ]);
    expect(stats.every((stat) => stat.href.startsWith("/management/"))).toBe(true);
  });
});

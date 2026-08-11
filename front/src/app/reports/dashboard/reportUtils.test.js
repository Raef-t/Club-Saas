import { describe, expect, it } from "vitest";
import { createOperationalReports, getReportCollection } from "./reportUtils";

describe("operational report utilities", () => {
  it("extracts supported backend collection shapes", () => {
    expect(getReportCollection({ data: { data: [{ id: 1 }] } })).toEqual([{ id: 1 }]);
    expect(getReportCollection({ data: [{ id: 2 }] })).toEqual([{ id: 2 }]);
  });

  it("counts only the latest active attendance per member", () => {
    const bundle = createOperationalReports({
      attendances: [
        {
          id: 1,
          member_id: 7,
          status: "checked_in",
          check_in: "2026-07-27T08:00:00",
          activity_name: "أجهزة عام",
        },
        {
          id: 2,
          member_id: 7,
          status: "checked_out",
          check_in: "2026-07-27T08:00:00",
          check_out: "2026-07-27T09:00:00",
          activity_name: "أجهزة عام",
        },
        {
          id: 3,
          member_id: 8,
          status: "checked_in",
          check_in: "2026-07-27T10:00:00",
          activity_name: "أجهزة خاص",
        },
      ],
      now: new Date(2026, 6, 27),
    });
    const hallReport = bundle.reports.find((report) => report.id === "hall");

    expect(hallReport.counts).toEqual({ total: 1, general: 0, private: 1 });
    expect(hallReport.rows[0].category).toBe("أجهزة خاص");
  });

  it("builds expired subscription and status reports from end dates", () => {
    const bundle = createOperationalReports({
      subscriptions: [
        {
          id: 1,
          status: "active",
          end_date: "2026-07-01",
          member: { id: 3, person: { full_name: "عضو منتهي" } },
          plan: { name: { ar: "شهري" } },
        },
        {
          id: 2,
          status: "active",
          end_date: "2026-08-01",
          member: { id: 4 },
          plan: { name: { ar: "سنوي" } },
        },
      ],
      now: new Date(2026, 6, 27),
    });
    const expiredReport = bundle.reports.find((report) => report.id === "expired");
    const statusReport = bundle.reports.find((report) => report.id === "subscriptions");

    expect(expiredReport.rows).toHaveLength(1);
    expect(statusReport.activeCount).toBe(1);
    expect(statusReport.rows).toEqual(
      expect.arrayContaining([
        expect.objectContaining({ status: "نشط", count: 1 }),
        expect.objectContaining({ status: "منتهٍ", count: 1 }),
      ]),
    );
  });

  it("groups unique activity subscribers from active subscriptions", () => {
    const bundle = createOperationalReports({
      subscriptions: [
        {
          id: 1,
          status: "active",
          member_id: 7,
          activities: [{ activity: { name: { ar: "يوغا" } } }],
        },
        {
          id: 2,
          status: "active",
          member_id: 7,
          items: [{ activity_name: "لياقة" }],
        },
        {
          id: 3,
          status: "active",
          member_id: 8,
          activities: [{ activity_name: "يوغا" }],
        },
      ],
      activities: [{ id: 1, name: { ar: "يوغا" } }],
      now: new Date(2026, 6, 27),
    });
    const report = bundle.reports.find((item) => item.id === "activities");

    expect(report.uniqueSubscribers).toBe(2);
    expect(report.rows).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          activity: "يوغا",
          subscribers: 2,
          subscriptions: 2,
        }),
      ]),
    );
  });

  it("uses work_status in the coaches report", () => {
    const bundle = createOperationalReports({
      coaches: [
        { id: 1, work_status: "suspended", person: { full_name: "مدرب موقوف" } },
        { id: 2, work_status: "on_leave", person: { full_name: "مدرب بإجازة" } },
      ],
    });
    const report = bundle.reports.find((item) => item.id === "coaches");

    expect(report.rows.map((row) => row.status)).toEqual(["موقوف", "إجازة"]);
    expect(report.metrics).toEqual(
      expect.arrayContaining([
        expect.objectContaining({ label: "الموقوفون", value: 1 }),
        expect.objectContaining({ label: "في إجازة", value: 1 }),
      ]),
    );
  });

  it("assigns a semantic icon to every summary statistic", () => {
    const { stats } = createOperationalReports({});

    expect(stats.map((stat) => stat.iconKey)).toEqual([
      "generalTraining",
      "privateTraining",
      "expiring",
      "activities",
      "subscriptions",
      "members",
    ]);
  });
});

import { describe, expect, it } from "vitest";
import {
  createAttendanceBranchOptions,
  createAttendanceMember,
  createAttendanceRows,
  createAttendanceSubscriptions,
  formatAttendanceTime,
  getInitialAttendanceSelection,
  toggleRequiredSubscription,
} from "./attendanceUtils";

describe("attendance utilities", () => {
  it("creates a member view model from the backend response", () => {
    expect(
      createAttendanceMember(
        {
          data: {
            id: 7,
            member_number: "M-7",
            person: { full_name: "أحمد علي", photo_url: "photo.jpg" },
          },
        },
        7,
      ),
    ).toMatchObject({
      id: 7,
      name: "أحمد علي",
      number: "M-7",
      avatar: "أ",
    });
  });

  it("formats valid times and rejects invalid values", () => {
    expect(formatAttendanceTime("invalid")).toBe("-");
    expect(formatAttendanceTime(null)).toBe("-");
  });

  it("maps attendance history with localized status values", () => {
    const rows = createAttendanceRows(
      {
        data: [
          {
            id: 10,
            member_id: 7,
            check_in: "2026-01-01T10:00:00Z",
            status: "checked_in",
          },
        ],
      },
      { name: "أحمد علي" },
    );

    expect(rows[0]).toMatchObject({
      id: 10,
      number: "#10",
      member: "أحمد علي",
      status: "دخول",
    });
  });

  it("creates a fallback activity when the subscription has no items", () => {
    const subscriptions = createAttendanceSubscriptions({
      data: [
        {
          player_subscription_id: 4,
          plan_id: 2,
          plan_name: "الخطة الشهرية",
          items: [],
        },
      ],
    });

    expect(subscriptions[0].activities).toEqual([{ id: "2", label: "الخطة الشهرية", coach: "-" }]);
  });

  it("selects subscriptions that still have sessions", () => {
    const selection = getInitialAttendanceSelection([
      {
        id: "1",
        remaining: 0,
        activities: [{ id: "10" }],
        activeLockers: [],
      },
      {
        id: "2",
        remaining: 4,
        activities: [{ id: "20" }],
        activeLockers: [{ locker_number: 8 }],
      },
    ]);

    expect(selection).toEqual({
      subscriptionIds: ["2"],
      activityId: "20",
      lockerNumber: "8",
    });
  });

  it("does not remove the final selected subscription", () => {
    expect(toggleRequiredSubscription(["1"], "1")).toEqual(["1"]);
    expect(toggleRequiredSubscription(["1"], "2")).toEqual(["1", "2"]);
  });

  it("normalizes branch response shapes for the scanner", () => {
    expect(
      createAttendanceBranchOptions({
        data: [{ id: 3, name: { ar: "الفرع الرئيسي" } }],
      }),
    ).toEqual([{ value: "3", label: "الفرع الرئيسي" }]);
  });
});

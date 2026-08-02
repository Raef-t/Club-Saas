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
      type: "عضو",
      status: "دخول",
    });
  });

  it("maps the global attendance response for staff without nested person data", () => {
    const rows = createAttendanceRows({
      status: "success",
      data: [
        {
          id: 4,
          attendable_type: "staff",
          attendable_id: 9,
          check_in: "2026-08-02T12:39:05+03:00",
          check_out: null,
          status: "checked_in",
        },
      ],
    });

    expect(rows[0]).toMatchObject({
      id: 4,
      attendableId: 9,
      attendableType: "staff",
      isOpen: true,
      type: "موظف",
      member: "موظف #9",
      checkOut: "-",
      status: "دخول",
    });
  });

  it("joins attendance ids with member and staff names", () => {
    const rows = createAttendanceRows(
      {
        data: [
          {
            id: 1,
            attendable_type: "member",
            attendable_id: 12,
            duration_minutes: 0,
            status: "completed",
          },
          {
            id: 2,
            attendable_type: "staff",
            attendable_id: 8,
            status: "checked_in",
          },
        ],
      },
      null,
      {
        members: { data: [{ id: 12, person: { full_name: "محمد علي" } }] },
        staff: { data: [{ id: 8, person: { full_name: "سامر حسن" } }] },
      },
    );

    expect(rows[0]).toMatchObject({ member: "محمد علي", duration: 0 });
    expect(rows[1]).toMatchObject({ member: "سامر حسن" });
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

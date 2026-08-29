import { describe, expect, it } from "vitest";
import {
  attachAttendanceLockers,
  createAttendanceDeductionBody,
  createAttendanceLockerReservation,
  createAttendanceBranchOptions,
  createAttendanceMember,
  createAttendanceRows,
  createAttendanceSubscriptions,
  createAvailableLockerOptions,
  createManualCheckInTimestamp,
  formatAttendanceDateTime,
  formatAttendanceTime,
  findAttendanceLockerId,
  getInitialAttendanceSelection,
  isAttendanceNoteRequiredMessage,
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
    expect(formatAttendanceDateTime("invalid")).toBe("-");
    expect(formatAttendanceDateTime(null)).toBe("-");
  });

  it("builds an optional manual check-in timestamp using the local date", () => {
    const date = new Date(2026, 7, 8, 12, 0, 0);

    expect(createManualCheckInTimestamp("14:30", date)).toBe("2026-08-08 14:30:00");
    expect(createManualCheckInTimestamp("", date)).toBeNull();
    expect(createManualCheckInTimestamp("25:00", date)).toBeNull();
  });

  it("reads the new check_in_at history field", () => {
    const [row] = createAttendanceRows({
      data: [
        {
          id: 15,
          attendable_type: "member",
          attendable_id: 7,
          check_in_at: "2026-08-08 14:30:00",
          status: "checked_in",
        },
      ],
    });

    expect(row.checkIn).not.toBe("-");
  });

  it("maps the formatted duration and full check-in timestamp from attendance history", () => {
    const [row] = createAttendanceRows({
      status: "success",
      data: [
        {
          id: 51,
          attendable_type: "member",
          attendable_id: 32,
          check_in: "2026-08-11T17:00:00+03:00",
          check_out: "2026-08-11T19:30:00+03:00",
          duration_minutes: 150,
          duration_formatted: "2 ساعة و 30 دقيقة",
          status: "completed",
        },
      ],
    });

    expect(row.duration).toBe("2 ساعة و 30 دقيقة");
    expect(row.checkIn).toMatch(/^\d{2}\/\d{2}\/\d{4} - /);
  });

  it("maps the consumed subscription plans from attendance history", () => {
    const rows = createAttendanceRows({
      data: [
        {
          id: 71,
          attendable_type: "member",
          attendable_id: 41,
          consumptions: [
            {
              id: 67,
              subscription_plan_id: 1,
              subscription_plan_name: "ميكس ايروبيك دانيه 5",
            },
          ],
          status: "completed",
        },
        {
          id: 72,
          attendable_type: "member",
          attendable_id: 41,
          consumptions: [],
          status: "completed",
        },
      ],
    });

    expect(rows[0].consumptions).toEqual([
      {
        id: 67,
        subscriptionPlanId: 1,
        subscriptionPlanName: "ميكس ايروبيك دانيه 5",
      },
    ]);
    expect(rows[1].consumptions).toEqual([]);
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

    expect(rows[0]).toMatchObject({ member: "محمد علي", duration: "0 دقيقة" });
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

  it("selects subscriptions that still have sessions without preselecting an occupied locker", () => {
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
      lockerNumber: "",
    });
  });

  it("creates naturally sorted options from available lockers only", () => {
    expect(
      createAvailableLockerOptions({
        data: {
          lockers: [
            { id: 1, locker_number: "10", status: "available", holder_id: null },
            { id: 2, locker_number: "2", status: "available", holder_id: null },
            { id: 3, locker_number: "3", status: "assigned", holder_id: 12 },
            { id: 4, locker_number: "4", status: "available", holder_id: 15 },
          ],
        },
      }),
    ).toEqual([
      { value: "2", label: "خزانة 2" },
      { value: "10", label: "خزانة 10" },
    ]);
  });

  it("builds attendance deduction and locker assignment payloads", () => {
    expect(createAttendanceDeductionBody(["4", "7"], "  حضور استثنائي  ")).toEqual({
      player_subscription_ids: [4, 7],
      notes: "حضور استثنائي",
    });
    expect(createAttendanceDeductionBody(["4"], "   ")).toEqual({
      player_subscription_ids: [4],
    });
    expect(createAttendanceLockerReservation(12, new Date(2026, 7, 8, 12))).toEqual({
      reservation_type: "assign",
      holder_type: "member",
      holder_id: 12,
      start_date: "2026-08-08",
    });
  });

  it("detects when the backend requires an off-schedule attendance reason", () => {
    expect(
      isAttendanceNoteRequiredMessage(
        "لا يمكن تسجيل الحضور: هذا ليس موعد فعاليتك المجدول. يرجى إدخال سبب تسجيل الحضور في هذا الوقت",
      ),
    ).toBe(true);
    expect(isAttendanceNoteRequiredMessage("تعذر الاتصال بالخادم")).toBe(false);
  });

  it("keeps locker details on attendance rows and resolves ids by locker number", () => {
    const response = {
      data: [
        {
          id: 21,
          status: "checked_in",
          active_locker: { id: 6, locker_number: "L-12" },
        },
      ],
    };

    expect(createAttendanceRows(response)[0]).toMatchObject({
      lockerId: 6,
      lockerNumber: "L-12",
      locker: "L-12",
    });
    expect(
      findAttendanceLockerId({ data: { lockers: [{ id: 6, locker_number: "L-12" }] } }, "L-12"),
    ).toBe(6);
  });

  it("attaches an occupied locker by its attendance holder", () => {
    expect(
      attachAttendanceLockers(
        [
          {
            id: 21,
            attendableType: "member",
            attendableId: 12,
            locker: "-",
            lockerId: null,
            lockerNumber: null,
          },
        ],
        {
          data: {
            lockers: [
              {
                id: 6,
                locker_number: "L-12",
                holder_type: "member",
                holder_id: 12,
              },
            ],
          },
        },
      )[0],
    ).toMatchObject({ lockerId: 6, lockerNumber: "L-12", locker: "L-12" });
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

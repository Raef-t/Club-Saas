import { describe, expect, it } from "vitest";
import {
  getNotificationAction,
  getNotifications,
  getUnreadNotificationsCount,
} from "./notifications";

describe("notification actions", () => {
  it("maps a payroll due notification to the internal payroll action", () => {
    const action = getNotificationAction({
      notification_id: 400,
      target_snapshot: {
        type: "payroll_due",
        branch_id: 11,
        period_start: "2026-08-03",
        period_end: "2026-09-02",
        action_url: "javascript:alert('ignored')",
      },
    });

    expect(action).toEqual({
      type: "payroll_due",
      branchId: "11",
      href: "/management/payroll?payroll_action=generate&branch_id=11&notification_id=400&period_start=2026-08-03&period_end=2026-09-02",
    });
  });

  it("rejects unrelated and invalid actions", () => {
    expect(getNotificationAction({ target_snapshot: { type: "other", branch_id: 11 } })).toBeNull();
    expect(
      getNotificationAction({ target_snapshot: { type: "payroll_due", branch_id: "oops" } }),
    ).toBeNull();
  });

  it("supports generated payroll types and JSON encoded snapshots", () => {
    expect(
      getNotificationAction({
        notification_id: 401,
        title: "تم إعداد مسير الرواتب بنجاح",
        target_snapshot: JSON.stringify({
          type: "payroll_generated",
          branch_id: 11,
          period_start: "2026-07-28",
          period_end: "2026-08-28",
        }),
      }),
    ).toEqual({
      type: "payroll_due",
      branchId: "11",
      href: "/management/payroll?payroll_action=generate&branch_id=11&notification_id=401&period_start=2026-07-28&period_end=2026-08-28",
    });
  });

  it("extracts a branch only from an allowlisted relative payroll action URL", () => {
    expect(
      getNotificationAction({
        target_snapshot: {
          type: "system_notice",
          action_url: "/dashboard/payroll/generate?branch_id=11",
        },
      }),
    ).toMatchObject({ branchId: "11", type: "payroll_due" });

    expect(
      getNotificationAction({
        target_snapshot: {
          type: "system_notice",
          action_url: "javascript:alert(1)",
          branch_id: 11,
        },
      }),
    ).toBeNull();
  });

  it("unwraps collections and counts unread notifications", () => {
    const notifications = getNotifications({
      data: { data: { data: [{ is_read: false }, { is_read: true }] } },
    });
    expect(notifications).toHaveLength(2);
    expect(getUnreadNotificationsCount(notifications)).toBe(1);
  });
});

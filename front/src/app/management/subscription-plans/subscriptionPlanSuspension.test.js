import { describe, expect, it } from "vitest";
import {
  getActiveSubscriptionPlanSuspension,
  getSubscriptionPlanSuspensionId,
  isSubscriptionPlanSuspended,
} from "./subscriptionPlanSuspension";

describe("subscription plan suspensions", () => {
  it("finds the active record in a suspensions relation", () => {
    const plan = {
      suspensions: [
        { id: 3, status: "completed", actual_end_date: "2026-08-10" },
        { id: 5, status: "active", actual_end_date: null },
      ],
    };

    expect(getActiveSubscriptionPlanSuspension(plan)).toEqual(plan.suspensions[1]);
    expect(getSubscriptionPlanSuspensionId(plan)).toBe(5);
    expect(isSubscriptionPlanSuspended(plan)).toBe(true);
  });

  it("supports a direct active suspension returned with plan details", () => {
    const activeSuspension = { id: 9, status: "active", actual_end_date: null };

    expect(getActiveSubscriptionPlanSuspension({ active_suspension: activeSuspension })).toBe(
      activeSuspension,
    );
  });

  it("reads the exact suspension shape returned by the plans list endpoint", () => {
    const plan = {
      id: 17,
      name: "اجهزة خاص يومي دانية",
      status: "active",
      is_suspended: true,
      active_suspension: {
        id: 12,
        suspend_start_date: "2026-08-15",
        suspend_end_date: "2026-08-22",
        actual_end_date: null,
        suspension_days: 8,
        reason: "ظرف صحي طارئ للكوتش",
        status: "active",
        coach_id: 44,
        coach_name: "كابتن دانية",
        affected_subscribers_count: 10,
      },
    };

    expect(isSubscriptionPlanSuspended(plan)).toBe(true);
    expect(getSubscriptionPlanSuspensionId(plan)).toBe(12);
    expect(getActiveSubscriptionPlanSuspension(plan)).toMatchObject({
      id: 12,
      suspension_days: 8,
      affected_subscribers_count: 10,
    });
  });

  it("ignores ended and deleted suspension records", () => {
    const plan = {
      suspensions: [
        { id: 1, status: "active", actual_end_date: "2026-08-15" },
        { id: 2, status: "active", deleted_at: "2026-08-15T10:00:00Z" },
      ],
    };

    expect(getActiveSubscriptionPlanSuspension(plan)).toBeNull();
    expect(isSubscriptionPlanSuspended(plan)).toBe(false);
  });

  it("uses the active suspension id exposed directly on a compact list item", () => {
    const plan = { is_suspended: true, active_suspension_id: 12 };

    expect(getSubscriptionPlanSuspensionId(plan)).toBe(12);
  });
});

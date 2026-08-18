import { describe, expect, it } from "vitest";
import { subscriptionEditSchema } from "./subscriptionsSchema";

describe("subscription edit validation", () => {
  it("normalizes the documented update payload", () => {
    const result = subscriptionEditSchema.parse({
      member_id: "1",
      plan_id: "1",
      offer_id: "1",
      months_count: "1",
      start_date: "2026-08-01",
      end_date: "2026-09-01",
      status: "active",
      paid_amount: "100",
      notes: "ملاحظات معدلة",
      reason: "  تصحيح مدة الاشتراك  ",
    });

    expect(result).toEqual({
      member_id: 1,
      plan_id: 1,
      offer_id: 1,
      months_count: 1,
      start_date: "2026-08-01",
      end_date: "2026-09-01",
      status: "active",
      paid_amount: 100,
      notes: "ملاحظات معدلة",
      reason: "تصحيح مدة الاشتراك",
    });
  });

  it("rejects an end date before the start date", () => {
    const result = subscriptionEditSchema.safeParse({
      member_id: 1,
      plan_id: 1,
      offer_id: null,
      months_count: 1,
      start_date: "2026-09-01",
      end_date: "2026-08-01",
      status: "active",
      paid_amount: 100,
      notes: "",
      reason: "تصحيح التواريخ",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues[0].path).toEqual(["end_date"]);
  });

  it("requires a non-empty modification reason", () => {
    const result = subscriptionEditSchema.safeParse({
      member_id: 1,
      plan_id: 1,
      months_count: 1,
      start_date: "2026-08-01",
      end_date: "2026-09-01",
      status: "active",
      paid_amount: 100,
      reason: "   ",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues.some((issue) => issue.path[0] === "reason")).toBe(true);
  });
});

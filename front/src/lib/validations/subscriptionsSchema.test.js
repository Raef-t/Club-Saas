import { describe, expect, it } from "vitest";
import { subscriptionEditSchema, subscriptionSchema } from "./subscriptionsSchema";

describe("subscription create validation", () => {
  const validSubscription = {
    member_id: 1,
    plan_id: 2,
    paid_amount: 300,
    months_count: 1,
    receipt_number: "  REC-0007  ",
    start_date: "2026-08-01",
    end_date: "2026-08-31",
  };

  it("requires and normalizes the receipt number", () => {
    expect(subscriptionSchema.parse(validSubscription).receipt_number).toBe("REC-0007");

    const result = subscriptionSchema.safeParse({
      ...validSubscription,
      receipt_number: "   ",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues.some((issue) => issue.path[0] === "receipt_number")).toBe(true);
  });

  it("uses two receipt numbers for a private plan and omits the general receipt", () => {
    const result = subscriptionSchema.parse({
      ...validSubscription,
      is_private_plan: true,
      receipt_number: "",
      coach_receipt_number: "  REC-COACH-001  ",
      branch_receipt_number: "  REC-CLUB-001  ",
    });

    expect(result).toMatchObject({
      coach_receipt_number: "REC-COACH-001",
      branch_receipt_number: "REC-CLUB-001",
    });
    expect(result).not.toHaveProperty("receipt_number");
    expect(result).not.toHaveProperty("is_private_plan");
  });

  it("requires both private-plan receipt numbers", () => {
    const result = subscriptionSchema.safeParse({
      ...validSubscription,
      is_private_plan: true,
      coach_receipt_number: "REC-COACH-001",
      branch_receipt_number: "",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues[0].path).toEqual(["branch_receipt_number"]);
  });
});

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
      payment_method: "cash",
      receipt_number: "  REC-2026-001  ",
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
      payment_method: "cash",
      receipt_number: "REC-2026-001",
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
      payment_method: "cash",
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
      payment_method: "cash",
      reason: "   ",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues.some((issue) => issue.path[0] === "reason")).toBe(true);
  });
});

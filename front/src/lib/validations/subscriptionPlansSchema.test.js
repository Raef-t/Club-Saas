import { describe, expect, it } from "vitest";
import { subscriptionPlanSchema, subscriptionPlanUpdateSchema } from "./subscriptionPlansSchema";

function createPlan(activities) {
  return {
    branch_id: "5",
    name: "زومبا - كابتن دانية",
    price: "300",
    activities,
  };
}

describe("subscription plan activity validation", () => {
  it("requires at least one activity", () => {
    const result = subscriptionPlanSchema.safeParse(createPlan([]));

    expect(result.success).toBe(false);
    expect(result.error.issues[0].path).toEqual(["activities"]);
  });

  it("requires a coach for regular activities", () => {
    const result = subscriptionPlanSchema.safeParse(
      createPlan([{ activity_id: 7, coach_id: null, coach_optional: false }]),
    );

    expect(result.success).toBe(false);
    expect(result.error.issues[0].path).toEqual(["activities", 0, "coach_id"]);
  });

  it("allows general equipment without a coach and removes the UI-only flag", () => {
    const result = subscriptionPlanSchema.parse(
      createPlan([{ activity_id: 3, coach_id: null, coach_optional: true }]),
    );

    expect(result.activities).toEqual([{ activity_id: 3, coach_id: null }]);
  });

  it("keeps the selected coach for regular activities", () => {
    const result = subscriptionPlanSchema.parse(
      createPlan([{ activity_id: 7, coach_id: 44, coach_optional: false }]),
    );

    expect(result.activities).toEqual([{ activity_id: 7, coach_id: 44 }]);
  });

  it("requires and normalizes both prices for a private plan", () => {
    const result = subscriptionPlanSchema.parse({
      ...createPlan([{ activity_id: 7, coach_id: 44 }]),
      is_private_plan: true,
      coach_price: "200",
      branch_price: "150",
    });

    expect(result.coach_price).toBe(200);
    expect(result.branch_price).toBe(150);

    const missingBranchPrice = subscriptionPlanSchema.safeParse({
      ...createPlan([{ activity_id: 7, coach_id: 44 }]),
      is_private_plan: true,
      coach_price: "200",
    });
    expect(missingBranchPrice.success).toBe(false);
    expect(missingBranchPrice.error.issues[0].path).toEqual(["branch_price"]);
  });

  it("does not persist legacy commission fields on the subscription plan", () => {
    const result = subscriptionPlanSchema.parse({
      ...createPlan([{ activity_id: 7, coach_id: 44 }]),
      club_commission_percentage: 20,
      coach_commission_percentage: 80,
    });

    expect(result).not.toHaveProperty("club_commission_percentage");
    expect(result).not.toHaveProperty("coach_commission_percentage");
  });

  it("requires and trims the update reason", () => {
    const payload = createPlan([{ activity_id: 3, coach_id: null, coach_optional: true }]);
    const missingReason = subscriptionPlanUpdateSchema.safeParse(payload);
    const result = subscriptionPlanUpdateSchema.parse({
      ...payload,
      reason: "  تصحيح سعر الفعالية  ",
    });

    expect(missingReason.success).toBe(false);
    expect(result.reason).toBe("تصحيح سعر الفعالية");
  });
});

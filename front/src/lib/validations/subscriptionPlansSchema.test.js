import { describe, expect, it } from "vitest";
import { subscriptionPlanSchema } from "./subscriptionPlansSchema";

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

  it("accepts complementary private-equipment commissions and removes the UI flag", () => {
    const result = subscriptionPlanSchema.parse({
      ...createPlan([{ activity_id: 3, coach_id: 44 }]),
      private_commission_required: true,
      club_commission_percentage: "20",
      coach_commission_percentage: "80",
    });

    expect(result.club_commission_percentage).toBe(20);
    expect(result.coach_commission_percentage).toBe(80);
    expect(result).not.toHaveProperty("private_commission_required");
  });

  it("rejects missing or non-complementary private-equipment commissions", () => {
    const missing = subscriptionPlanSchema.safeParse({
      ...createPlan([{ activity_id: 3, coach_id: 44 }]),
      private_commission_required: true,
    });
    const invalidTotal = subscriptionPlanSchema.safeParse({
      ...createPlan([{ activity_id: 3, coach_id: 44 }]),
      private_commission_required: true,
      club_commission_percentage: 20,
      coach_commission_percentage: 70,
    });

    expect(missing.success).toBe(false);
    expect(invalidTotal.success).toBe(false);
  });
});

import { describe, expect, it } from "vitest";
import { subscriptionPlanSuspensionSchema } from "./subscriptionPlanSuspensionSchema";

describe("subscription plan suspension validation", () => {
  it("accepts the API payload and trims its reason", () => {
    const result = subscriptionPlanSuspensionSchema.parse({
      suspend_start_date: "2026-08-15",
      suspend_end_date: "2026-08-22",
      reason: "  ظرف صحي طارئ للمدرب  ",
    });

    expect(result.reason).toBe("ظرف صحي طارئ للمدرب");
  });

  it("rejects an end date before the start date", () => {
    const result = subscriptionPlanSuspensionSchema.safeParse({
      suspend_start_date: "2026-08-22",
      suspend_end_date: "2026-08-15",
      reason: "ظرف صحي طارئ للمدرب",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues[0].path).toEqual(["suspend_end_date"]);
  });
});

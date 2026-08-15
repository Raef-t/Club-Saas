import { describe, expect, it } from "vitest";
import {
  getSubscriptionPlanStatus,
  getSubscriptionPlanStatusMeta,
  isSubscriptionPlanActive,
} from "./subscriptionPlanStatus";

describe("subscription plan statuses", () => {
  it.each([
    ["active", "نشطة"],
    ["inactive", "غير نشطة"],
    ["completed", "مكتملة"],
  ])("keeps the backend %s status", (status, label) => {
    expect(getSubscriptionPlanStatus({ status })).toBe(status);
    expect(getSubscriptionPlanStatusMeta(status).label).toBe(label);
  });

  it("supports legacy is_active responses", () => {
    expect(getSubscriptionPlanStatus({ is_active: true })).toBe("active");
    expect(getSubscriptionPlanStatus({ is_active: false })).toBe("inactive");
  });

  it("only treats the active status as active", () => {
    expect(isSubscriptionPlanActive({ status: "active" })).toBe(true);
    expect(
      isSubscriptionPlanActive({
        status: "active",
        active_suspension: { id: 5, status: "active", actual_end_date: null },
      }),
    ).toBe(false);
    expect(isSubscriptionPlanActive({ status: "inactive" })).toBe(false);
    expect(isSubscriptionPlanActive({ status: "completed" })).toBe(false);
  });

  it("shows a temporary suspension without changing the permanent plan status", () => {
    const plan = {
      status: "active",
      active_suspension: { id: 5, status: "active", actual_end_date: null },
    };

    expect(getSubscriptionPlanStatus(plan)).toBe("active");
    expect(getSubscriptionPlanStatusMeta(plan)).toMatchObject({
      status: "suspended",
      label: "موقوفة مؤقتاً",
    });
  });
});

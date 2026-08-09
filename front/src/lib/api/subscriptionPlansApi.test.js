import { describe, expect, it } from "vitest";
import {
  mergeSubscriptionPlanIntoResponse,
  normalizeSubscriptionPlanPlayersResponse,
} from "./subscriptionPlansApi";

describe("subscription plans API cache", () => {
  it("keeps a plan in the list after it becomes inactive", () => {
    const response = {
      status: "success",
      data: [{ id: 5, name: "سباحة", status: "active" }],
    };

    mergeSubscriptionPlanIntoResponse(response, { id: 5, status: "inactive" });

    expect(response.data).toEqual([{ id: 5, name: "سباحة", status: "inactive" }]);
  });

  it("adds a returned plan when it is missing from the cached list", () => {
    const response = { data: [] };

    mergeSubscriptionPlanIntoResponse(response, { id: 8, status: "completed" });

    expect(response.data).toEqual([{ id: 8, status: "completed" }]);
  });
});

describe("subscription plan players response", () => {
  it("extracts active players and their reported total", () => {
    const response = {
      status: "success",
      data: {
        plan_id: 1,
        plan_name: "ميكس ايروبيك",
        total_active_subscribers: 2,
        players: [
          { subscription_id: 10, full_name: "ماسة البيك" },
          { subscription_id: 12, full_name: "نايا البيك" },
        ],
      },
    };

    expect(normalizeSubscriptionPlanPlayersResponse(response)).toEqual(response.data);
  });

  it("returns a safe empty result for a malformed response", () => {
    expect(normalizeSubscriptionPlanPlayersResponse(null)).toEqual({
      plan_id: null,
      plan_name: "",
      total_active_subscribers: 0,
      players: [],
    });
  });
});

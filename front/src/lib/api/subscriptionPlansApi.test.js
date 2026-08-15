import { describe, expect, it } from "vitest";
import {
  clearSubscriptionPlanSuspensionFromResponse,
  mergeSubscriptionPlanIntoResponse,
  mergeSubscriptionPlanSuspensionIntoResponse,
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

describe("subscription plan suspension cache", () => {
  it("stores the returned suspension on the matching list item", () => {
    const response = { data: [{ id: 5, status: "active" }] };
    const suspension = { id: 11, status: "active", actual_end_date: null };

    mergeSubscriptionPlanSuspensionIntoResponse(response, 5, suspension);

    expect(response.data[0]).toMatchObject({
      is_suspended: true,
      active_suspension_id: 11,
      active_suspension: suspension,
      suspensions: [suspension],
    });
  });

  it("clears the active suspension after an early resume", () => {
    const response = {
      data: {
        id: 5,
        is_suspended: true,
        active_suspension_id: 11,
        active_suspension: { id: 11, status: "active" },
        suspensions: [{ id: 11, status: "active", actual_end_date: null }],
      },
    };

    clearSubscriptionPlanSuspensionFromResponse(response, 5, 11);

    expect(response.data).toMatchObject({
      is_suspended: false,
      active_suspension_id: null,
      active_suspension: null,
    });
    expect(response.data.suspensions[0]).toMatchObject({
      status: "completed",
    });
    expect(response.data.suspensions[0].actual_end_date).toBeTruthy();
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

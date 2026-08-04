import { describe, expect, it } from "vitest";
import { mergeSubscriptionPlanIntoResponse } from "./subscriptionPlansApi";

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

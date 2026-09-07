import { describe, expect, it } from "vitest";
import { addMinutesToTime } from "./subscriptionPlanTimeUtils";

describe("subscription plan time utilities", () => {
  it("sets the default end time sixty minutes after the start", () => {
    expect(addMinutesToTime("11:00")).toBe("12:00");
    expect(addMinutesToTime("11:15")).toBe("12:15");
  });

  it("wraps times that cross midnight", () => {
    expect(addMinutesToTime("23:30")).toBe("00:30");
  });

  it("returns an empty value for an incomplete time", () => {
    expect(addMinutesToTime("")).toBe("");
    expect(addMinutesToTime("11")).toBe("");
  });
});

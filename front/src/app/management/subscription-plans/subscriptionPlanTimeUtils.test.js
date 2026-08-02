import { describe, expect, it } from "vitest";
import { addMinutesToTime } from "./subscriptionPlanTimeUtils";

describe("subscription plan time utilities", () => {
  it("sets the default end time ninety minutes after the start", () => {
    expect(addMinutesToTime("11:00")).toBe("12:30");
    expect(addMinutesToTime("11:15")).toBe("12:45");
  });

  it("wraps times that cross midnight", () => {
    expect(addMinutesToTime("23:30")).toBe("01:00");
  });

  it("returns an empty value for an incomplete time", () => {
    expect(addMinutesToTime("")).toBe("");
    expect(addMinutesToTime("11")).toBe("");
  });
});

import { describe, expect, it } from "vitest";
import { getDurationInDays, getDurationInMonths } from "./durationHelpers";

describe("offer duration conversions", () => {
  it.each([
    [1, 30],
    [1.5, 45],
    [3, 90],
    [12, 365],
    [24, 730],
  ])("converts %s months to %s API days", (months, days) => {
    expect(getDurationInDays(months)).toBe(days);
  });

  it.each([
    [30, 1],
    [45, 1.5],
    [90, 3],
    [365, 12],
    [730, 24],
  ])("restores %s API days as %s months", (days, months) => {
    expect(getDurationInMonths(days)).toBe(months);
  });
});

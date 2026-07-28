import { describe, expect, it } from "vitest";
import { applyDateMask, formatDateDisplay, parseDateInput } from "./datePickerUtils";
import { applyTimeMask, padTimeSegment, parseTimeInput } from "./timePickerUtils";

describe("date picker utilities", () => {
  it("formats and parses supported date formats", () => {
    expect(formatDateDisplay("2026-07-26")).toBe("26/07/2026");
    expect(parseDateInput("07/26/2026", "MM/DD/YYYY")).toBe("2026-07-26");
    expect(applyDateMask("20260726", "YYYY/MM/DD")).toBe("2026/07/26");
  });

  it("rejects impossible dates", () => {
    expect(parseDateInput("31/02/2026")).toBeNull();
  });
});

describe("time picker utilities", () => {
  it("masks and validates 24-hour values", () => {
    expect(applyTimeMask("0930")).toBe("09:30");
    expect(padTimeSegment(7)).toBe("07");
    expect(parseTimeInput("23:59")).toBe("23:59");
    expect(parseTimeInput("24:00")).toBeNull();
  });
});

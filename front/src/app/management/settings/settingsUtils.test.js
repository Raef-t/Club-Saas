import { describe, expect, it, vi } from "vitest";
import {
  clearFieldError,
  createBranchSettingsForm,
  createBranchSettingsPayload,
  createHolidayPayload,
  createShiftPayload,
  escapeHtml,
  formatTimeForApi,
  getBranchName,
  getHolidayPeriod,
  getSettingsCollection,
  getSettingsRecord,
} from "./settingsUtils";

describe("settings utilities", () => {
  it("normalizes backend time values", () => {
    expect(formatTimeForApi("08:30:00")).toBe("08:30");
    expect(formatTimeForApi("")).toBeNull();
  });

  it("returns localized branch names", () => {
    expect(getBranchName({ name: { ar: "الفرع الرئيسي", en: "Main" } })).toBe("الفرع الرئيسي");
    expect(getBranchName({ name: "Downtown" })).toBe("Downtown");
  });

  it("removes only the requested field error", () => {
    const setter = vi.fn((updater) => {
      expect(updater({ name: "Required", startTime: "Required" })).toEqual({
        startTime: "Required",
      });
    });

    clearFieldError(setter, "name");
    expect(setter).toHaveBeenCalledOnce();
  });

  it("normalizes settings response shapes", () => {
    expect(getSettingsRecord({ data: { locker_price: 25 } })).toEqual({
      locker_price: 25,
    });
    expect(getSettingsRecord({ data: { data: { locker_price: 30 } } })).toEqual({
      locker_price: 30,
    });
    expect(getSettingsCollection({ data: { data: [{ id: 1 }] } })).toEqual([{ id: 1 }]);
  });

  it("creates branch settings forms and payloads without losing zero values", () => {
    const form = createBranchSettingsForm({
      default_club_commission_percentage: 0,
      working_hours_start: "08:30:00",
      allow_freeze: 1,
    });

    expect(form.defaultClubCommission).toBe("0");
    expect(form.workingHoursStart).toBe("08:30");
    expect(form.allowFreeze).toBe(true);
    expect(createBranchSettingsPayload(form)).toMatchObject({
      default_club_commission_percentage: 0,
      working_hours_start: "08:30",
      allow_freeze: true,
    });
  });

  it("creates shift and holiday backend payloads", () => {
    expect(
      createShiftPayload({
        shiftName: " صباحية ",
        shiftStartTime: "08:00:00",
        shiftEndTime: "16:00",
        shiftGender: "mixed",
      }),
    ).toEqual({
      name: "صباحية",
      day_of_week: 0,
      start_time: "08:00",
      end_time: "16:00",
      gender_allowed: "mixed",
    });

    expect(
      createHolidayPayload({
        holidayType: "specific_dates",
        holidayDay: "5",
        holidayStartDate: "2026-01-01",
        holidayEndDate: "2026-01-02",
      }),
    ).toEqual({
      type: "specific_dates",
      day_of_week: null,
      start_date: "2026-01-01",
      end_date: "2026-01-02",
    });
  });

  it("escapes printable values and formats holiday periods", () => {
    expect(escapeHtml('<script>"x"</script>')).toBe("&lt;script&gt;&quot;x&quot;&lt;/script&gt;");
    expect(getHolidayPeriod({ type: "weekly", day_of_week: 5 }, { 5: "الجمعة" })).toBe(
      "كل يوم الجمعة",
    );
  });
});

import { describe, expect, it } from "vitest";
import {
  createScheduleDataFromApi,
  createScheduleSettingsFromApi,
  createWeeklyHolidayDayKeys,
  escapeScheduleHtml,
  generateTimeSlots,
} from "./scheduleUtils";
import { buildSchedulePrintHtml } from "./schedulePrint";
import { SCHEDULE_DAYS, SCHEDULE_DEFAULT_SETTINGS } from "./scheduleConstants";

describe("schedule utilities", () => {
  it("uses the club morning and evening opening periods", () => {
    expect(SCHEDULE_DEFAULT_SETTINGS).toMatchObject({
      morningStart: "08:00",
      morningEnd: "16:00",
      eveningStart: "16:00",
      eveningEnd: "23:00",
    });

    expect(
      generateTimeSlots(
        SCHEDULE_DEFAULT_SETTINGS.morningStart,
        SCHEDULE_DEFAULT_SETTINGS.morningEnd,
        SCHEDULE_DEFAULT_SETTINGS.slotDuration,
      ),
    ).toHaveLength(8);
    expect(
      generateTimeSlots(
        SCHEDULE_DEFAULT_SETTINGS.eveningStart,
        SCHEDULE_DEFAULT_SETTINGS.eveningEnd,
        SCHEDULE_DEFAULT_SETTINGS.slotDuration,
      ),
    ).toHaveLength(7);
  });

  it("starts the displayed week on Saturday", () => {
    expect(SCHEDULE_DAYS.map((day) => day.key)).toEqual([
      "sat",
      "sun",
      "mon",
      "tue",
      "wed",
      "thu",
      "fri",
    ]);
  });

  it("uses the selected branch opening and closing times", () => {
    expect(
      createScheduleSettingsFromApi({
        data: { working_hours_start: "07:30:00", working_hours_end: "22:30:00" },
      }),
    ).toMatchObject({ morningStart: "07:30", eveningEnd: "22:30" });

    expect(
      createScheduleSettingsFromApi({
        data: { working_hours_start: "08:00:00", working_hours_end: "01:00:00" },
      }),
    ).toMatchObject({ morningStart: "08:00", eveningEnd: "01:00" });
  });

  it("keeps periods inside branches with early or late working hours", () => {
    expect(
      createScheduleSettingsFromApi({
        data: { working_hours_start: "06:00:00", working_hours_end: "14:00:00" },
      }),
    ).toMatchObject({
      morningStart: "06:00",
      morningEnd: "14:00",
      eveningStart: "14:00",
      eveningEnd: "14:00",
    });

    expect(
      createScheduleSettingsFromApi({
        data: { working_hours_start: "18:00:00", working_hours_end: "23:00:00" },
      }),
    ).toMatchObject({
      morningStart: "18:00",
      morningEnd: "18:00",
      eveningStart: "18:00",
      eveningEnd: "23:00",
    });
  });

  it("maps recurring weekly holidays to schedule rows", () => {
    expect(createWeeklyHolidayDayKeys({ data: [{ type: "weekly", day_of_week: 5 }] })).toEqual([
      "fri",
    ]);
  });

  it("generates fixed slots without extending beyond the period", () => {
    expect(generateTimeSlots("10:00", "14:00", 90)).toEqual([
      {
        key: "1000",
        from: "10:00",
        to: "11:30",
        label: "10:00",
      },
      {
        key: "1130",
        from: "11:30",
        to: "13:00",
        label: "11:30",
      },
    ]);
  });

  it("supports periods that cross midnight", () => {
    expect(generateTimeSlots("22:00", "01:00", 60)).toHaveLength(3);
    expect(generateTimeSlots("22:00", "01:00", 60)[2]).toMatchObject({
      from: "00:00",
      to: "01:00",
    });
  });

  it("rejects invalid times and durations", () => {
    expect(generateTimeSlots("invalid", "14:00", 60)).toEqual([]);
    expect(generateTimeSlots("10:00", "14:00", 0)).toEqual([]);
    expect(generateTimeSlots("14:00", "14:00", 60)).toEqual([]);
  });

  it("maps backend sessions only to their matching period", () => {
    const morningSlots = generateTimeSlots("10:00", "12:00", 60);
    const eveningSlots = generateTimeSlots("15:00", "17:00", 60);
    const result = createScheduleDataFromApi(
      {
        data: {
          Friday: [
            {
              start_time: "15:00:00",
              plan_name: "لياقة",
              coach: { name: "سارة" },
            },
          ],
        },
      },
      morningSlots,
      eveningSlots,
    );

    expect(result.fri).toEqual({
      evening_1500: "لياقة - سارة",
    });
    expect(result.fri.morning_1500).toBeUndefined();
  });

  it("maps sessions with minute-based times into the containing schedule slot", () => {
    const morningSlots = generateTimeSlots("10:00", "12:00", 60);
    const result = createScheduleDataFromApi(
      {
        data: {
          Sunday: [
            {
              start_time: "10:30:00",
              end_time: "11:15:00",
              plan_name: "لياقة",
              coach_name: "سارة",
            },
          ],
        },
      },
      morningSlots,
      [],
    );

    expect(result.sun).toEqual({
      morning_1000: "لياقة - سارة",
    });
  });

  it("keeps multiple sessions that start inside the same schedule slot", () => {
    const morningSlots = generateTimeSlots("10:00", "11:00", 60);
    const result = createScheduleDataFromApi(
      {
        data: {
          Sunday: [
            { start_time: "10:10:00", plan_name: "لياقة" },
            { start_time: "10:30:00", plan_name: "يوغا" },
          ],
        },
      },
      morningSlots,
      [],
    );

    expect(result.sun).toEqual({
      morning_1000: "لياقة\nيوغا",
    });
  });

  it("keeps only schedule sessions owned by the selected branch", () => {
    const morningSlots = generateTimeSlots("10:00", "12:00", 60);
    const result = createScheduleDataFromApi(
      {
        data: {
          Sunday: [
            {
              branch_id: 1,
              start_time: "10:00:00",
              plan_name: "فرع أول",
            },
            {
              branch_id: 2,
              start_time: "11:00:00",
              plan_name: "فرع ثانٍ",
            },
          ],
        },
      },
      morningSlots,
      [],
      "2",
    );

    expect(result.sun).toEqual({
      morning_1100: "فرع ثانٍ",
    });
  });

  it("escapes printable user content", () => {
    expect(escapeScheduleHtml(`<script>"test" & 'x'</script>`)).toBe(
      "&lt;script&gt;&quot;test&quot; &amp; &#039;x&#039;&lt;/script&gt;",
    );
  });

  it("escapes schedule cells inside the print document", () => {
    const html = buildSchedulePrintHtml({
      morningSlots: [{ key: "1000", from: "10:00", to: "11:00" }],
      eveningSlots: [],
      scheduleData: {
        sun: { morning_1000: "<img src=x onerror=alert(1)>" },
      },
      holidayDayKeys: ["sun"],
      printedAt: new Date("2026-01-01T00:00:00Z"),
    });

    expect(html).toContain("&lt;img src=x onerror=alert(1)&gt;");
    expect(html).not.toContain("<img src=x onerror=alert(1)>");
    expect(html).toMatch(/<tr class="[^"]*holiday-row[^"]*">/);
    expect(html).toContain('class="holiday-badge">عطلة</span>');
  });
});

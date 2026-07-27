import { describe, expect, it } from "vitest";
import { createScheduleDataFromApi, escapeScheduleHtml, generateTimeSlots } from "./scheduleUtils";
import { buildSchedulePrintHtml } from "./schedulePrint";

describe("schedule utilities", () => {
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
      printedAt: new Date("2026-01-01T00:00:00Z"),
    });

    expect(html).toContain("&lt;img src=x onerror=alert(1)&gt;");
    expect(html).not.toContain("<img src=x onerror=alert(1)>");
  });
});

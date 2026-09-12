import { describe, expect, it } from "vitest";
import {
  createShiftAttendanceParams,
  formatShiftTime,
  normalizeShiftAttendanceRecord,
  normalizeShiftAttendanceResponse,
  validateShiftAttendanceFilters,
  createPrintableShiftAttendanceReport,
} from "./shiftAttendanceReportUtils";

describe("shiftAttendanceReportUtils", () => {
  it("formats shift time nicely", () => {
    expect(formatShiftTime("13:00:00")).toBe("1:00 م");
    expect(formatShiftTime("09:30:00")).toBe("9:30 ص");
    expect(formatShiftTime("00:15:00")).toBe("12:15 ص");
    expect(formatShiftTime("12:00:00")).toBe("12:00 م");
    expect(formatShiftTime("-")).toBe("-");
  });

  it("builds query parameters accurately for date mode", () => {
    const filters = {
      mode: "date",
      date: "2026-07-30",
      activityId: "3",
      shiftId: "2",
    };

    const params = createShiftAttendanceParams(filters, "5");

    expect(params).toEqual({
      date: "2026-07-30",
      branch_id: "5",
      activity_id: "3",
      shift_id: "2",
    });
  });

  it("builds query parameters accurately for month and range mode", () => {
    const monthFilters = {
      mode: "month",
      month: "2026-07",
    };
    expect(createShiftAttendanceParams(monthFilters, "all")).toEqual({
      month: "2026-07",
    });

    const rangeFilters = {
      mode: "range",
      startDate: "2026-07-01",
      endDate: "2026-07-31",
    };
    expect(createShiftAttendanceParams(rangeFilters, "5")).toEqual({
      start_date: "2026-07-01",
      end_date: "2026-07-31",
      branch_id: "5",
    });
  });

  it("normalizes a shift attendance record correctly", () => {
    const raw = {
      shift_id: 2,
      shift_name: "شيفت الظهر",
      branch_id: 5,
      branch_name: "تكنو جيم بنات",
      start_time: "13:00:00",
      end_time: "18:00:00",
      attended_players_count: "25",
      unique_players_count: "20",
      crowd_percentage: "65.5",
    };

    const normalized = normalizeShiftAttendanceRecord(raw, 0);

    expect(normalized.id).toBe(2);
    expect(normalized.shiftName).toBe("شيفت الظهر");
    expect(normalized.timeFormatted).toBe("1:00 م - 6:00 م");
    expect(normalized.attendedPlayersCount).toBe(25);
    expect(normalized.uniquePlayersCount).toBe(20);
    expect(normalized.crowdPercentage).toBe(65.5);
  });

  it("normalizes entire response with summary", () => {
    const rawResponse = {
      status: "success",
      data: {
        summary: {
          period_label: "يوم 2026-07-30",
          total_shift_attendances: "50",
          total_shifts_count: "2",
          busiest_shift: { shift_name: "شيفت المساء", crowd_percentage: 80 },
          quietest_shift: { shift_name: "شيفت الصباح", crowd_percentage: 20 },
        },
        records: [
          { shift_id: 1, shift_name: "صباحي", attended_players_count: 10 },
        ],
      },
    };

    const result = normalizeShiftAttendanceResponse(rawResponse);

    expect(result.summary.period_label).toBe("يوم 2026-07-30");
    expect(result.summary.total_shift_attendances).toBe(50);
    expect(result.summary.total_shifts_count).toBe(2);
    expect(result.summary.busiest_shift.shift_name).toBe("شيفت المساء");
    expect(result.records).toHaveLength(1);
  });

  it("validates date range filters", () => {
    expect(
      validateShiftAttendanceFilters({
        mode: "range",
        startDate: "2026-08-01",
        endDate: "2026-07-01",
      })
    ).toBe("يجب أن يكون تاريخ البداية قبل تاريخ النهاية أو مساوياً له.");

    expect(
      validateShiftAttendanceFilters({
        mode: "range",
        startDate: "2026-07-01",
        endDate: "2026-08-01",
      })
    ).toBe("");
  });

  it("creates printable report format", () => {
    const rows = [
      normalizeShiftAttendanceRecord({
        shift_id: 2,
        shift_name: "شيفت الظهر",
        start_time: "13:00:00",
        end_time: "18:00:00",
        attended_players_count: 15,
        unique_players_count: 12,
        crowd_percentage: 50,
      }),
    ];
    const summary = {
      period_label: "يوم 2026-07-30",
      total_shift_attendances: 15,
      total_shifts_count: 1,
      busiest_shift: { shift_name: "شيفت الظهر" },
      quietest_shift: { shift_name: "شيفت الظهر" },
    };

    const printable = createPrintableShiftAttendanceReport(rows, summary);

    expect(printable.id).toBe("shifts-attendance-crowd");
    expect(printable.metrics).toHaveLength(5);
    expect(printable.rows).toHaveLength(1);
  });
});

import { describe, expect, it } from "vitest";
import {
  createPeakHoursReportParams,
  getHolidayLabel,
  normalizePeakHoursReportResponse,
  validatePeakHoursFilters,
} from "./peakHoursReportUtils";

describe("peakHoursReportUtils", () => {
  it("builds the documented query parameters", () => {
    expect(
      createPeakHoursReportParams(
        {
          startDate: "2026-07-01",
          endDate: "2026-07-31",
          attendableType: "member",
        },
        "5",
      ),
    ).toEqual({
      start_date: "2026-07-01",
      end_date: "2026-07-31",
      branch_id: "5",
      attendable_type: "member",
    });
  });

  it("omits all-type and all-branch values", () => {
    expect(createPeakHoursReportParams({ attendableType: "all" }, "all")).toEqual({});
  });

  it("normalizes summary, hourly, and daily response values", () => {
    const report = normalizePeakHoursReportResponse({
      status: "success",
      data: {
        summary: {
          busiest_day: "الأحد",
          quietest_day: "الجمعة",
          peak_hours_range: "18:00 - 20:00",
          off_peak_hours_range: "N/A",
          total_attendances_analyzed: "25",
          excluded_holidays_count: "1",
        },
        top_peak_hours: [{ hour: 18, label: "18:00", count: "10" }],
        top_off_peak_hours: [],
        hourly_breakdown: [{ hour: 0, label: "00:00", count: "2" }],
        daily_breakdown: [
          {
            day_number: 5,
            day_name: "الجمعة",
            total_check_ins: "3",
            is_weekly_holiday: true,
          },
        ],
        specific_holidays: [{ date: "2026-07-10", name: "عطلة" }],
      },
    });

    expect(report.summary).toMatchObject({
      busiest_day: "الأحد",
      off_peak_hours_range: "غير متاح",
      total_attendances_analyzed: 25,
      excluded_holidays_count: 1,
    });
    expect(report.topPeakHours[0].count).toBe(10);
    expect(report.hourlyBreakdown[0]).toMatchObject({ hour: 0, label: "00:00", count: 2 });
    expect(report.dailyBreakdown[0]).toMatchObject({
      id: 5,
      dayName: "الجمعة",
      totalCheckIns: 3,
      isWeeklyHoliday: true,
    });
    expect(report.specificHolidays).toHaveLength(1);
  });

  it("validates the date range and formats holiday objects", () => {
    expect(
      validatePeakHoursFilters({ startDate: "2026-07-31", endDate: "2026-07-01" }),
    ).toBeTruthy();
    expect(getHolidayLabel({ name: "عطلة", date: "2026-07-10" })).toBe("عطلة — 2026-07-10");
  });
});

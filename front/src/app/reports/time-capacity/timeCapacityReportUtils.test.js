import { describe, expect, it } from "vitest";
import {
  createTimeCapacityReportParams,
  flattenTimeCapacityActivities,
  normalizeTimeCapacityReportResponse,
  validateTimeCapacityFilters,
} from "./timeCapacityReportUtils";

describe("timeCapacityReportUtils", () => {
  it("builds the documented API query and preserves Sunday as day zero", () => {
    expect(
      createTimeCapacityReportParams(
        {
          startTime: "10:00",
          endTime: "14:00:00",
          dayOfWeek: "0",
          planId: "5",
          activityId: "2",
        },
        "5",
      ),
    ).toEqual({
      start_time: "10:00:00",
      end_time: "14:00:00",
      day_of_week: "0",
      branch_id: "5",
      plan_id: "5",
      activity_id: "2",
    });
  });

  it("omits empty and all filters", () => {
    expect(
      createTimeCapacityReportParams(
        { startTime: "", endTime: "", dayOfWeek: "all", planId: "", activityId: "" },
        "all",
      ),
    ).toEqual({});
  });

  it("normalizes the documented empty response", () => {
    expect(
      normalizeTimeCapacityReportResponse({
        status: "success",
        data: {
          summary: { total_activities: "2", total_active_subscribers: "18" },
          activities: [],
        },
      }),
    ).toEqual({
      summary: {
        total_activities: 2,
        total_coaches: 0,
        total_plans: 0,
        total_active_subscribers: 18,
      },
      activities: [],
    });
  });

  it("flattens grouped activities, coaches, plans, and time slots", () => {
    const rows = flattenTimeCapacityActivities([
      {
        id: 2,
        name: { ar: "يوغا" },
        coaches: [
          {
            id: 7,
            person: { full_name: "كوتش سارة" },
            plans: [
              {
                id: 5,
                name: { ar: "خطة اليوغا" },
                time_slots: [
                  {
                    id: 11,
                    day_of_week: 0,
                    start_time: "10:00:00",
                    end_time: "11:00:00",
                    capacity: 20,
                    active_subscribers_count: 15,
                  },
                ],
              },
            ],
          },
        ],
      },
    ]);

    expect(rows).toHaveLength(1);
    expect(rows[0]).toMatchObject({
      activityName: "يوغا",
      coachName: "كوتش سارة",
      planName: "خطة اليوغا",
      dayOfWeek: "الأحد",
      startTime: "10:00:00",
      endTime: "11:00:00",
      capacity: 20,
      activeSubscribers: 15,
      remainingCapacity: 5,
      utilization: 75,
      capacityStatus: "available",
    });
  });

  it("rejects invalid or inverted time ranges", () => {
    expect(validateTimeCapacityFilters({ startTime: "14:00", endTime: "10:00" })).toBeTruthy();
    expect(validateTimeCapacityFilters({ startTime: "invalid", endTime: "" })).toBeTruthy();
  });
});

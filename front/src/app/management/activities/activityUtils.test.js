import { describe, expect, it } from "vitest";
import {
  createActivityFormValues,
  createActivityPayload,
  createActivityStats,
  filterActivities,
  getActivityCollection,
  getActivityRecord,
} from "./activityUtils";

const activities = [
  { id: 1, name: { ar: "السباحة", en: "Swimming" }, is_active: true },
  { id: 2, name: "Yoga", description: "مرونة", is_active: false },
];

describe("activity utilities", () => {
  it("normalizes activity response shapes", () => {
    expect(getActivityCollection({ data: { data: activities } })).toEqual(activities);
    expect(getActivityRecord({ data: { data: activities[0] } })).toEqual(activities[0]);
  });

  it("filters localized names and descriptions", () => {
    expect(filterActivities(activities, "سباحة")).toEqual([activities[0]]);
    expect(filterActivities(activities, "مرونة")).toEqual([activities[1]]);
  });

  it("creates activity statistics", () => {
    const stats = createActivityStats(activities);
    expect(stats.map((item) => Number(item.value))).toEqual([2, 1, 1]);
  });

  it("creates form values and a normalized payload", () => {
    const form = createActivityFormValues({
      ...activities[0],
      branch: { id: 3 },
      activity_type: { id: 4 },
      shifts: [{ id: "7" }],
    });

    expect(form).toMatchObject({
      name: "السباحة",
      branch_id: "3",
      activity_type_id: "4",
      shifts: [7],
    });
    expect(createActivityPayload({ ...form, description: " " }, true)).toMatchObject({
      branch_id: 3,
      activity_type_id: 4,
      description: null,
      shifts: [7],
    });
  });
});

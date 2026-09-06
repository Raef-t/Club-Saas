import { describe, expect, it } from "vitest";
import {
  activityTypeHasShifts,
  createActivityFormValues,
  createActivityPayload,
  createActivityStats,
  filterActivities,
  getActivityCollection,
  getActivityRecord,
} from "./activityUtils";
import { activityUpdateSchema } from "@/lib/validations/activitiesSchema";

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

  it("uses the backend activity-type flag to decide whether shifts are available", () => {
    const activityTypes = [
      { id: 4, name: "نوع بدون ورديات", has_shifts: false },
      { id: 99, name: "نوع مخصص مع ورديات", has_shifts: true },
    ];

    expect(activityTypeHasShifts(activityTypes, "99")).toBe(true);
    expect(activityTypeHasShifts(activityTypes, "4")).toBe(false);
  });

  it("includes a trimmed modification reason only in update payloads", () => {
    const form = {
      ...createActivityFormValues(),
      name: "يوغا",
      branch_id: "3",
      activity_type_id: "4",
      reason: "  تصحيح وصف النشاط  ",
    };

    expect(createActivityPayload(form, false)).not.toHaveProperty("reason");
    expect(createActivityPayload(form, false, true)).toHaveProperty("reason", "تصحيح وصف النشاط");
  });

  it("requires a modification reason when validating an activity update", () => {
    const result = activityUpdateSchema.safeParse({
      name: "يوغا",
      description: "",
      gender_allowed: "mixed",
      branch_id: 3,
      activity_type_id: 4,
      is_active: true,
      shifts: [],
      reason: "   ",
    });

    expect(result.success).toBe(false);
    expect(result.error.issues.some((issue) => issue.path[0] === "reason")).toBe(true);
  });
});

import { describe, expect, it } from "vitest";
import {
  COACH_ACTIVITY_KINDS,
  createCoachFormInitialValues,
  getCoachActivityKind,
  getCoachRulesForActivities,
  getEmploymentTypeForWorkTypes,
  calculateAge,
} from "./coachFormUtils";

describe("coach form utilities", () => {
  it("selects the first branch for a new coach", () => {
    const values = createCoachFormInitialValues(null, [{ id: "12" }]);

    expect(values.branch_ids).toEqual([12]);
    expect(values.shifts).toEqual([]);
    expect(values).not.toHaveProperty("national_id");
    expect(values.start_date).toBe("");
    expect(values.work_status).toBe("active");
    expect(values).not.toHaveProperty("shift_ids");
  });

  it("preserves edit values", () => {
    const values = createCoachFormInitialValues({
      first_name: "Test",
      last_name: "Coach",
      dob: "1990-01-01",
      branch_ids: [3],
      work_status: "on_leave",
    });
    expect(values.first_name).toBe("Test");
    expect(values.last_name).toBe("Coach");
    expect(values.dob).toBe("1990-01-01");
    expect(values.branch_ids).toEqual([3]);
    expect(values.work_status).toBe("on_leave");
  });

  it("maps work types to employment types", () => {
    expect(getEmploymentTypeForWorkTypes(["equipment"])).toBe("fixed_salary");
    expect(getEmploymentTypeForWorkTypes(["activities"])).toBe("commission_based");
    expect(getEmploymentTypeForWorkTypes(["equipment", "activities"])).toBe("hybrid");
  });

  it("recognizes localized backend activity types", () => {
    expect(getCoachActivityKind({ activity_type: { name: { ar: "تدريب عام" } } })).toBe(
      COACH_ACTIVITY_KINDS.GENERAL_TRAINING,
    );
    expect(getCoachActivityKind({ activity_type: { code: "private_training" } })).toBe(
      COACH_ACTIVITY_KINDS.PRIVATE_TRAINING,
    );
    expect(getCoachActivityKind({ activity_type: { name: { en: "Group Class" } } })).toBe(
      COACH_ACTIVITY_KINDS.GROUP_CLASS,
    );
    expect(getCoachActivityKind({ activity_type_name: "دخول يومي" })).toBe(
      COACH_ACTIVITY_KINDS.DAILY_ENTRY,
    );
  });

  it("derives equipment, salary, and shifts for general training", () => {
    const rules = getCoachRulesForActivities([{ activity_type: { name: { ar: "تدريب عام" } } }]);

    expect(rules.workTypes).toEqual(["equipment"]);
    expect(rules.employmentType).toBe("fixed_salary");
    expect(rules.allowsSalary).toBe(true);
    expect(rules.allowsCommission).toBe(false);
    expect(rules.allowsShifts).toBe(true);
  });

  it("derives commission-only class work for a group class", () => {
    const rules = getCoachRulesForActivities([{ activity_type: { name: { ar: "حصة جماعية" } } }]);

    expect(rules.workTypes).toEqual(["activities"]);
    expect(rules.employmentType).toBe("commission_based");
    expect(rules.allowsSalary).toBe(false);
    expect(rules.allowsCommission).toBe(true);
    expect(rules.allowsShifts).toBe(false);
  });

  it("removes compensation and shifts from private training", () => {
    const rules = getCoachRulesForActivities([{ activity_type: { name: { ar: "تدريب خاص" } } }]);

    expect(rules.allowsSalary).toBe(false);
    expect(rules.allowsCommission).toBe(false);
    expect(rules.allowsShifts).toBe(false);
  });

  it("marks group classes mixed with equipment training as incompatible", () => {
    const rules = getCoachRulesForActivities([
      { activity_type: { code: "general_training" } },
      { activity_type: { code: "group_class" } },
    ]);

    expect(rules.hasIncompatibleActivities).toBe(true);
  });

  it("calculates age correctly from date of birth", () => {
    expect(calculateAge("")).toBeNull();
    expect(calculateAge(null)).toBeNull();
    expect(calculateAge("invalid-date")).toBeNull();

    // Past date (e.g. 1995-01-01)
    const age1995 = calculateAge("1995-01-01");
    expect(typeof age1995).toBe("number");
    expect(age1995).toBeGreaterThan(25);
  });
});

import { describe, expect, it } from "vitest";
import {
  COACH_ACTIVITY_KINDS,
  createCoachEditInitialValues,
  createCoachFormInitialValues,
  getCoachActivityKind,
  getCoachRulesForActivities,
  getEmploymentTypeForWorkTypes,
  calculateAge,
} from "./coachFormUtils";

describe("coach form utilities", () => {
  it("maps the nested phone fields from the coach details response", () => {
    const values = createCoachEditInitialValues({
      id: 51,
      branch_ids: [5],
      employment_type: "commission_based",
      base_salary: "700.00",
      start_date: "2026-08-09",
      work_status: "active",
      person: {
        full_name: "رانية التنجي",
        gender: "female",
        dob: "1975-08-01",
        phone_number: "999999999",
        country_code: "+963",
      },
      details: {
        experience_years: 20,
        work_types: ["activities"],
        default_commission_rate: "40.00",
      },
      activities: [{ id: 8 }],
      shifts: [{ id: 4, branch_shift_id: 1 }],
    });

    expect(values.phone_number).toBe("999999999");
    expect(values.country_code).toBe("+963");
    expect(values.first_name).toBe("رانية");
    expect(values.last_name).toBe("التنجي");
  });

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

  it("removes salary, commission, and shifts from private-only equipment training", () => {
    const rules = getCoachRulesForActivities([{ activity_type: { name: { ar: "تدريب خاص" } } }]);

    expect(rules.employmentType).toBe("commission_based");
    expect(rules.allowsSalary).toBe(false);
    expect(rules.allowsCommission).toBe(false);
    expect(rules.allowsShifts).toBe(false);
  });

  it("uses a fixed salary when private and general equipment training are combined", () => {
    const rules = getCoachRulesForActivities([
      { activity_type: { code: "private_training" } },
      { activity_type: { code: "general_training" } },
    ]);

    expect(rules.workTypes).toEqual(["equipment"]);
    expect(rules.employmentType).toBe("fixed_salary");
    expect(rules.allowsSalary).toBe(true);
    expect(rules.allowsCommission).toBe(false);
    expect(rules.allowsShifts).toBe(true);
    expect(rules.hasIncompatibleActivities).toBe(false);
  });

  it("uses a fixed salary when private equipment training is combined with an activity", () => {
    const rules = getCoachRulesForActivities([
      { activity_type: { code: "private_training" } },
      { activity_type: { code: "group_class" } },
    ]);

    expect(rules.workTypes).toEqual(["equipment", "activities"]);
    expect(rules.employmentType).toBe("fixed_salary");
    expect(rules.allowsSalary).toBe(true);
    expect(rules.allowsCommission).toBe(false);
    expect(rules.allowsShifts).toBe(false);
    expect(rules.hasIncompatibleActivities).toBe(false);
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

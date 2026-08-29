import { describe, expect, it } from "vitest";
import {
  createCoachActivityPlansMap,
  formatCoachCommission,
  getCoachActivityPlanKey,
  getCoachAge,
  getCoachCompensationVisibility,
  getCoachCommissionItems,
  getCoachBranchNames,
  getUnassignedActivities,
  isPrivateEquipmentCoach,
} from "./coachDetailsUtils";

describe("coach details utilities", () => {
  it("groups linked events by coach and activity instead of activity alone", () => {
    const plansMap = createCoachActivityPlansMap([
      {
        id: 10,
        name: { ar: "فعالية الكوتش الأول" },
        session_count: 12,
        activities: [{ activity_id: 4, coach_id: 7 }],
      },
      {
        id: 11,
        name: { ar: "فعالية الكوتش الثاني" },
        session_count: 8,
        activities: [{ activity_id: 4, coach_id: 9 }],
      },
      {
        id: 12,
        name: { ar: "فعالية بلا كوتش" },
        activities: [{ activity_id: 4 }],
      },
    ]);

    expect(plansMap.get(getCoachActivityPlanKey(7, 4))).toEqual([
      expect.objectContaining({ id: 10, name: "فعالية الكوتش الأول" }),
    ]);
    expect(plansMap.get(getCoachActivityPlanKey(9, 4))).toEqual([
      expect.objectContaining({ id: 11, name: "فعالية الكوتش الثاني" }),
    ]);
    expect([...plansMap.values()].flat()).toHaveLength(2);
  });

  it("resolves localized branch names", () => {
    const coach = { branch_ids: [2, 7] };
    const branches = [{ id: 2, name: { ar: "الرئيسي" } }];
    expect(getCoachBranchNames(coach, branches)).toBe("الرئيسي، فرع #7");
  });

  it("filters already assigned activities", () => {
    expect(getUnassignedActivities([{ id: 1 }], [{ id: 1 }, { id: 2 }])).toEqual([{ id: 2 }]);
  });

  it.each([
    ["fixed_salary", true, false],
    ["commission_based", false, true],
    ["hybrid", true, true],
  ])("maps %s compensation fields", (paymentType, showSalary, showCommission) => {
    expect(
      getCoachCompensationVisibility({
        employment_type: paymentType,
        details: { payment_type: paymentType, work_types: ["activities"] },
      }),
    ).toMatchObject({ showSalary, showCommission });
  });

  it("shows commission for a private-only equipment coach", () => {
    const coach = {
      employment_type: "commission_based",
      base_salary: "0.00",
      details: {
        payment_type: "commission_based",
        default_commission_rate: "40.00",
        work_types: ["equipment"],
      },
      activities: [
        {
          activity_type: { code: "private_training" },
          is_private_equipment: 1,
        },
      ],
    };

    expect(isPrivateEquipmentCoach(coach)).toBe(true);
    expect(getCoachCompensationVisibility(coach)).toMatchObject({
      isPrivateEquipment: true,
      showSalary: false,
      showCommission: true,
    });
  });

  it.each([
    ["general_training", ["equipment"]],
    ["group_class", ["equipment", "activities"]],
  ])(
    "shows salary and commission when private equipment is combined with %s",
    (activityType, workTypes) => {
      const coach = {
        employment_type: "hybrid",
        details: { payment_type: "hybrid", work_types: workTypes },
        activities: [
          { activity_type: { code: "private_training" }, is_private_equipment: 1 },
          { activity_type: { code: activityType } },
        ],
      };

      expect(isPrivateEquipmentCoach(coach)).toBe(false);
      expect(getCoachCompensationVisibility(coach)).toMatchObject({
        isPrivateEquipment: false,
        showSalary: true,
        showCommission: true,
      });
    },
  );

  it("formats commission percentages", () => {
    expect(formatCoachCommission("40.00")).toBe("40%");
    expect(formatCoachCommission("12.50")).toBe("12.5%");
  });

  it("shows the private-training rate instead of a zero activity rate", () => {
    const items = getCoachCommissionItems({
      employment_type: "hybrid",
      work_types: ["equipment"],
      details: {
        default_commission_rate: 0,
        private_commission_rate: 100,
      },
      activities: [
        { activity_type: { code: "general_training" } },
        { activity_type: { code: "private_training" }, is_private_equipment: 1 },
      ],
    });

    expect(items).toEqual([expect.objectContaining({ key: "private", value: "100%" })]);
  });

  it("keeps activity and private-training rates separate when a coach has both", () => {
    const items = getCoachCommissionItems({
      work_types: ["equipment", "activities"],
      details: {
        default_commission_rate: 15.5,
        private_commission_rate: 70,
      },
      activities: [
        { activity_type: { code: "group_class" } },
        { activity_type: { code: "private_training" } },
      ],
    });

    expect(items).toEqual([
      expect.objectContaining({ key: "activity", value: "15.5%" }),
      expect.objectContaining({ key: "private", value: "70%" }),
    ]);
  });

  it("uses the API age and falls back to the date of birth", () => {
    expect(getCoachAge({ person: { age: 25, dob: "1990-01-01" } })).toBe(25);
    expect(getCoachAge({ person: { dob: "2000-01-01" } })).toBeGreaterThan(20);
    expect(getCoachAge({ person: {} })).toBeNull();
  });
});

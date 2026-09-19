import { describe, expect, it } from "vitest";
import { branchSettingsSchema, shiftSchema } from "./settingsSchema";

const validSettings = {
  selectedBranchId: "1",
  defaultClubCommission: 40,
  defaultCoachCommission: 60,
  privateSubscriptionCommission: 0,
  defaultEmployeeSalary: 3500,
  payrollEndDay: 30,
  workingHoursStart: "08:00",
  workingHoursEnd: "17:00",
  dailyEntryPrice: 10,
  lockerPrice: 5,
};

describe("branch settings validation", () => {
  it("accepts working hours that end on the same day", () => {
    expect(branchSettingsSchema.safeParse(validSettings).success).toBe(true);
  });

  it.each([
    ["منتصف الليل", "00:00"],
    ["الواحدة صباحاً", "01:00"],
  ])("accepts working hours that continue until %s", (_label, workingHoursEnd) => {
    const result = branchSettingsSchema.safeParse({
      ...validSettings,
      workingHoursEnd,
    });

    expect(result.success).toBe(true);
  });

  it("rejects identical opening and closing times", () => {
    const result = branchSettingsSchema.safeParse({
      ...validSettings,
      workingHoursEnd: validSettings.workingHoursStart,
    });

    expect(result.success).toBe(false);
    expect(result.error?.issues).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          path: ["workingHoursEnd"],
          message: "يجب أن يختلف وقت النهاية عن وقت البداية",
        }),
      ]),
    );
  });
});

const validShift = {
  shiftName: "وردية مسائية",
  shiftStartTime: "18:00",
  shiftEndTime: "23:00",
  shiftGender: "mixed",
};

describe("shift validation", () => {
  it("accepts a shift that ends on the same day", () => {
    expect(shiftSchema.safeParse(validShift).success).toBe(true);
  });

  it.each([
    ["مسائية", "18:00", "01:00"],
    ["ممتدة", "08:00", "01:00"],
  ])(
    "accepts a %s shift that ends on the following day",
    (_label, shiftStartTime, shiftEndTime) => {
      const result = shiftSchema.safeParse({
        ...validShift,
        shiftStartTime,
        shiftEndTime,
      });

      expect(result.success).toBe(true);
    },
  );

  it("rejects identical start and end times", () => {
    const result = shiftSchema.safeParse({
      ...validShift,
      shiftEndTime: validShift.shiftStartTime,
    });

    expect(result.success).toBe(false);
    expect(result.error?.issues).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          path: ["shiftEndTime"],
          message: "يجب أن يختلف وقت النهاية عن وقت البداية",
        }),
      ]),
    );
  });
});

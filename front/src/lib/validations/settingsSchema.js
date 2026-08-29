import { z } from "zod";
import "./zodErrorMap";

const requiredNumber = (label, { min = 0, max } = {}) => {
  let schema = z
    .union([z.string(), z.number()])
    .refine(
      (value) => String(value).trim() !== "" && Number.isFinite(Number(value)),
      `${label} مطلوب ويجب أن يكون رقماً صالحاً`,
    )
    .transform(Number)
    .refine((value) => value >= min, `${label} يجب ألا يقل عن ${min}`);

  if (max !== undefined) {
    schema = schema.refine((value) => value <= max, `${label} يجب ألا يزيد عن ${max}`);
  }

  return schema;
};

export const branchSettingsSchema = z
  .object({
    selectedBranchId: z.string().trim().min(1, "يرجى اختيار الفرع"),
    defaultClubCommission: requiredNumber("نسبة عمولة النادي", {
      max: 100,
    }),
    defaultCoachCommission: requiredNumber("نسبة عمولة المدرب", {
      max: 100,
    }),
    privateSubscriptionCommission: requiredNumber("نسبة النادي من التدريب الخاص", {
      max: 100,
    }),
    defaultEmployeeSalary: requiredNumber("راتب الموظف الافتراضي"),
    payrollEndDay: requiredNumber("يوم إقفال الرواتب الشهري", { min: 1, max: 31 }),
    workingHoursStart: z.string(),
    workingHoursEnd: z.string(),
    dailyEntryPrice: requiredNumber("سعر الدخول اليومي"),
    lockerPrice: requiredNumber("سعر الخزانة"),
  })
  .superRefine((data, context) => {
    if (data.workingHoursStart && !data.workingHoursEnd) {
      context.addIssue({
        code: "custom",
        path: ["workingHoursEnd"],
        message: "يرجى تحديد وقت نهاية ساعات العمل",
      });
    }

    if (!data.workingHoursStart && data.workingHoursEnd) {
      context.addIssue({
        code: "custom",
        path: ["workingHoursStart"],
        message: "يرجى تحديد وقت بداية ساعات العمل",
      });
    }

    if (
      data.workingHoursStart &&
      data.workingHoursEnd &&
      data.workingHoursEnd <= data.workingHoursStart
    ) {
      context.addIssue({
        code: "custom",
        path: ["workingHoursEnd"],
        message: "يجب أن يكون وقت النهاية بعد وقت البداية",
      });
    }
  });

export const shiftSchema = z
  .object({
    shiftName: z.string().trim().max(100, "اسم الوردية يجب ألا يتجاوز 100 حرف"),
    shiftStartTime: z.string().min(1, "وقت البدء مطلوب"),
    shiftEndTime: z.string().min(1, "وقت الانتهاء مطلوب"),
    shiftGender: z.enum(["mixed", "male", "female"], {
      message: "يرجى اختيار الفئة المسموح بها",
    }),
  })
  .superRefine((data, context) => {
    if (data.shiftStartTime && data.shiftEndTime && data.shiftEndTime <= data.shiftStartTime) {
      context.addIssue({
        code: "custom",
        path: ["shiftEndTime"],
        message: "يجب أن يكون وقت الانتهاء بعد وقت البدء",
      });
    }
  });

export const holidaySchema = z
  .object({
    holidayType: z.enum(["weekly", "specific_dates"]),
    holidayDay: z.string(),
    holidayStartDate: z.string(),
    holidayEndDate: z.string(),
  })
  .superRefine((data, context) => {
    if (data.holidayType === "weekly") {
      if (!/^[0-6]$/.test(data.holidayDay)) {
        context.addIssue({
          code: "custom",
          path: ["holidayDay"],
          message: "يرجى اختيار يوم الإجازة",
        });
      }
      return;
    }

    if (!data.holidayStartDate) {
      context.addIssue({
        code: "custom",
        path: ["holidayStartDate"],
        message: "تاريخ البدء مطلوب",
      });
    }

    if (!data.holidayEndDate) {
      context.addIssue({
        code: "custom",
        path: ["holidayEndDate"],
        message: "تاريخ الانتهاء مطلوب",
      });
    }

    if (
      data.holidayStartDate &&
      data.holidayEndDate &&
      data.holidayEndDate < data.holidayStartDate
    ) {
      context.addIssue({
        code: "custom",
        path: ["holidayEndDate"],
        message: "يجب أن يكون تاريخ الانتهاء بعد تاريخ البدء",
      });
    }
  });

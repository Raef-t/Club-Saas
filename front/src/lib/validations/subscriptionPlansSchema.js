import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonObjectSchema } from "./modificationReasonSchema";

const subscriptionPlanActivitySchema = z
  .object({
    activity_id: z.number().positive("يرجى اختيار نشاط صالح"),
    coach_id: z.number().positive("يرجى اختيار مدرب صالح").nullable(),
    coach_optional: z.boolean().optional().default(false),
  })
  .superRefine((activity, ctx) => {
    if (!activity.coach_optional && !activity.coach_id) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "المدرب مطلوب لهذا النشاط",
        path: ["coach_id"],
      });
    }
  })
  .transform(({ coach_optional, ...activity }) => activity);

const optionalCommissionPercentage = (label) =>
  z.preprocess(
    (value) => (value === "" || value === null || value === undefined ? undefined : Number(value)),
    z
      .number({ invalid_type_error: `${label} مطلوبة` })
      .min(0, `${label} يجب ألا تقل عن 0`)
      .max(100, `${label} يجب ألا تزيد عن 100`)
      .optional(),
  );

export const subscriptionPlanSchema = z
  .object({
    branch_id: z
      .string({ required_error: "الفرع مطلوب" })
      .min(1, "الفرع مطلوب")
      .or(z.number().transform(String)),

    name: z
      .string({ required_error: "اسم الخطة مطلوب" })
      .trim()
      .min(2, "اسم الخطة يجب أن يكون حرفين على الأقل")
      .max(100, "اسم الخطة طويل جداً"),

    sessions_per_week: z
      .union([z.number(), z.string().transform((val) => (val === "" ? undefined : Number(val)))])
      .optional()
      .nullable(),

    session_count: z
      .union([z.number(), z.string().transform((val) => (val === "" ? undefined : Number(val)))])
      .optional()
      .nullable(),

    price: z
      .number({ invalid_type_error: "السعر مطلوب" })
      .nonnegative("لا يمكن أن يكون السعر سالباً")
      .or(z.string().min(1, "السعر مطلوب").transform(Number)),

    max_subscribers: z
      .number({ invalid_type_error: "الحد الأقصى للمشتركين مطلوب" })
      .nonnegative("لا يمكن أن يكون الحد الأقصى للمشتركين سالباً")
      .or(z.string().min(1, "الحد الأقصى للمشتركين مطلوب").transform(Number))
      .nullable()
      .optional(),

    is_unlimited_subscribers: z.boolean().optional(),

    gender_restriction: z.enum(["mixed", "male", "female"]).optional(),

    activities: z.array(subscriptionPlanActivitySchema).min(1, "يرجى إضافة نشاط واحد على الأقل"),

    club_commission_percentage: optionalCommissionPercentage("نسبة النادي"),
    coach_commission_percentage: optionalCommissionPercentage("نسبة المدرب"),
    private_commission_required: z.boolean().optional().default(false),

    session_templates: z
      .array(
        z.object({
          day_of_week: z.number().min(0).max(6, "يوم الأسبوع غير صالح"),
          start_time: z.string().min(1, "وقت البدء مطلوب"),
          end_time: z.string().min(1, "وقت الانتهاء مطلوب"),
        }),
      )
      .optional(),

    is_active: z.boolean().optional(),
    status: z.enum(["active", "inactive", "completed"]).optional(),
  })
  .superRefine((data, ctx) => {
    if (data.private_commission_required) {
      if (data.club_commission_percentage === undefined) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "نسبة النادي مطلوبة لأجهزة خاص",
          path: ["club_commission_percentage"],
        });
      }

      if (data.coach_commission_percentage === undefined) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "نسبة المدرب مطلوبة لأجهزة خاص",
          path: ["coach_commission_percentage"],
        });
      }

      if (
        data.club_commission_percentage !== undefined &&
        data.coach_commission_percentage !== undefined &&
        Math.abs(data.club_commission_percentage + data.coach_commission_percentage - 100) > 0.001
      ) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "مجموع نسبة النادي والمدرب يجب أن يساوي 100%",
          path: ["club_commission_percentage"],
        });
      }
    }

    // if (data.type === "fixed_period") {
    //   if (data.duration_in_days === undefined || data.duration_in_days === null || Number.isNaN(data.duration_in_days)) {
    //     ctx.addIssue({
    //       code: z.ZodIssueCode.custom,
    //       message: "المدة بالأيام مطلوبة",
    //       path: ["duration_in_days"],
    //     });
    //   } else if (data.duration_in_days <= 0) {
    //     ctx.addIssue({
    //       code: z.ZodIssueCode.custom,
    //       message: "المدة يجب أن تكون أكبر من صفر",
    //       path: ["duration_in_days"],
    //     });
    //   }
    // }
    // if (data.type === "session_based") {
    //   if (data.session_count === undefined || data.session_count === null || Number.isNaN(data.session_count)) {
    //     ctx.addIssue({
    //       code: z.ZodIssueCode.custom,
    //       message: "عدد الجلسات مطلوب",
    //       path: ["session_count"],
    //     });
    //   } else if (data.session_count <= 0) {
    //     ctx.addIssue({
    //       code: z.ZodIssueCode.custom,
    //       message: "عدد الجلسات يجب أن يكون أكبر من صفر",
    //       path: ["session_count"],
    //     });
    //   }
    // }
  })
  .transform(({ private_commission_required, ...plan }) => plan);

export const subscriptionPlanUpdateSchema = subscriptionPlanSchema.and(
  modificationReasonObjectSchema,
);

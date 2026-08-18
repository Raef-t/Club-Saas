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

export const subscriptionPlanSchema = z.object({
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
});

export const subscriptionPlanUpdateSchema = subscriptionPlanSchema.and(
  modificationReasonObjectSchema,
);

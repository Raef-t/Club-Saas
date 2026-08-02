import { z } from "zod";
import "./zodErrorMap";

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
    .union([
      z.number(),
      z.string().transform((val) => (val === "" ? undefined : Number(val))),
    ])
    .optional()
    .nullable(),

  session_count: z
    .union([
      z.number(),
      z.string().transform((val) => (val === "" ? undefined : Number(val))),
    ])
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
  
  activities: z.array(
    z.object({
      activity_id: z.number().positive("يرجى اختيار نشاط صالح"),
      coach_id: z.number().positive("يرجى اختيار مدرب صالح"),
    })
  ).optional(),

  session_templates: z.array(
    z.object({
      day_of_week: z.number().min(0).max(6, "يوم الأسبوع غير صالح"),
      start_time: z.string().min(1, "وقت البدء مطلوب"),
      end_time: z.string().min(1, "وقت الانتهاء مطلوب"),
    })
  ).optional(),

  is_active: z.boolean().optional(),
  status: z.enum(["active", "inactive", "completed"]).optional(),
}).superRefine((data, ctx) => {
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
});

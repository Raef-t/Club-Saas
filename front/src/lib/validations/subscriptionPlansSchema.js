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

  type: z.enum(["fixed_period", "session_based"], {
    required_error: "نوع الخطة مطلوب",
  }),

  duration_in_days: z
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

  max_freeze_count: z
    .number({ invalid_type_error: "أقصى عدد مرات تجميد مطلوب" })
    .nonnegative("لا يمكن أن يكون عدد مرات التجميد سالباً")
    .or(z.string().min(1, "أقصى عدد مرات تجميد مطلوب").transform(Number)),

  max_freeze_days: z
    .number({ invalid_type_error: "أقصى إجمالي أيام تجميد مطلوب" })
    .nonnegative("لا يمكن أن يكون إجمالي أيام التجميد سالباً")
    .or(z.string().min(1, "أقصى إجمالي أيام تجميد مطلوب").transform(Number)),

  max_subscribers: z
    .number({ invalid_type_error: "الحد الأقصى للمشتركين مطلوب" })
    .nonnegative("لا يمكن أن يكون الحد الأقصى للمشتركين سالباً")
    .or(z.string().min(1, "الحد الأقصى للمشتركين مطلوب").transform(Number)),

  is_active: z.boolean().optional(),
}).superRefine((data, ctx) => {
  if (data.type === "fixed_period") {
    if (data.duration_in_days === undefined || data.duration_in_days === null || Number.isNaN(data.duration_in_days)) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "المدة بالأيام مطلوبة",
        path: ["duration_in_days"],
      });
    } else if (data.duration_in_days <= 0) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "المدة يجب أن تكون أكبر من صفر",
        path: ["duration_in_days"],
      });
    }
  }

  if (data.type === "session_based") {
    if (data.session_count === undefined || data.session_count === null || Number.isNaN(data.session_count)) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "عدد الجلسات مطلوب",
        path: ["session_count"],
      });
    } else if (data.session_count <= 0) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "عدد الجلسات يجب أن يكون أكبر من صفر",
        path: ["session_count"],
      });
    }
  }
});

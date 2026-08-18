import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonSchema } from "./modificationReasonSchema";

export const memberSchema = z.object({
  first_name: z
    .string({ required_error: "الاسم الأول مطلوب" })
    .min(2, "الاسم يجب أن يكون حرفين على الأقل")
    .max(50, "الاسم طويل جداً"),

  last_name: z
    .string({ required_error: "الاسم الأخير مطلوب" })
    .min(2, "الاسم الأخير يجب أن يكون حرفين على الأقل")
    .max(50, "الاسم الأخير طويل جداً"),

  mobile: z.string().max(15, "رقم الهاتف طويل جداً").optional().or(z.literal("")),

  mobile_country_code: z.string().optional().or(z.literal("")),

  gender: z.enum(["male", "female"], {
    required_error: "يرجى تحديد الجنس",
  }),

  dob: z.string().optional().or(z.literal("")),

  age: z.number().optional().or(z.string().transform(Number)).or(z.literal("")).optional(),

  branch_id: z
    .number({ invalid_type_error: "يرجى اختيار الفرع" })
    .positive("يرجى اختيار الفرع")
    .or(z.string().min(1, "يرجى اختيار الفرع").transform(Number)),

  emergency_name: z.string().max(100, "اسم الطوارئ طويل جداً").optional().or(z.literal("")),

  emergency_relation: z.string().optional().or(z.literal("")),

  emergency_phone: z.string().max(15, "رقم هاتف الطوارئ طويل جداً").optional().or(z.literal("")),

  emergency_country_code: z.string().optional().or(z.literal("")),

  plan_id: z.number().positive().or(z.string().transform(Number)).optional().or(z.literal("")),

  paid_amount: z
    .number()
    .nonnegative()
    .or(z.string().transform((val) => Number(val) || 0))
    .optional(),
});

export const memberUpdateSchema = memberSchema.extend({
  reason: modificationReasonSchema,
});

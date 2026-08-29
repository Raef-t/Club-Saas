import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonSchema } from "./modificationReasonSchema";

export const activitySchema = z.object({
  name: z
    .string({ required_error: "الاسم مطلوب" })
    .min(2, "الاسم يجب أن يكون حرفين على الأقل")
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف"),

  description: z.string().max(500, "الوصف طويل جداً").optional().or(z.literal("")),

  gender_allowed: z.enum(["mixed", "male", "female"], {
    required_error: "يرجى تحديد قيود الجنس",
  }),

  branch_id: z
    .number({ invalid_type_error: "يرجى اختيار الفرع" })
    .positive("يرجى اختيار الفرع")
    .or(z.string().min(1, "يرجى اختيار الفرع").transform(Number)),

  activity_type_id: z
    .number({ invalid_type_error: "يرجى اختيار نوع الفئة" })
    .positive("يرجى اختيار نوع الفئة")
    .or(z.string().min(1, "يرجى اختيار نوع الفئة").transform(Number)),

  is_active: z.boolean().optional(),
  shifts: z.array(z.number()).optional(),
});

export const activityUpdateSchema = activitySchema.extend({
  reason: modificationReasonSchema,
});

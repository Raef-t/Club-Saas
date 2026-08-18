import { z } from "zod";
import "./zodErrorMap";

export const clubSchema = z.object({
  name: z
    .string({ required_error: "اسم النادي مطلوب" })
    .min(2, "الاسم يجب أن يكون حرفين على الأقل")
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف"),

  logo: z.any().optional().nullable(),

  logo_url: z
    .string()
    .url("الرابط غير صالح")
    .optional()
    .or(z.literal(""))
    .or(z.null()),

  is_active: z.boolean().optional(),
});

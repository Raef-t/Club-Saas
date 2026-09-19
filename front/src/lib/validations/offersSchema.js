import { z } from "zod";
import "./zodErrorMap";

export const offerSchema = z.object({
  branch_id: z
    .union([z.number().positive("الفرع مطلوب"), z.string().min(1, "الفرع مطلوب").transform(Number)])
    .refine((val) => Number.isFinite(val) && val > 0, "الفرع مطلوب"),

  name: z
    .string({ required_error: "اسم العرض مطلوب" })
    .trim()
    .min(2, "اسم العرض يجب أن يكون حرفين على الأقل")
    .max(255, "اسم العرض طويل جداً"),

  description: z
    .string()
    .trim()
    .max(1000, "الوصف طويل جداً")
    .optional()
    .nullable()
    .or(z.literal("")),

  offer_type: z.enum(["bundle", "single_choice"]).default("bundle"),

  price: z
    .union([
      z.number({ invalid_type_error: "السعر يجب أن يكون رقماً" }),
      z.string().min(1, "سعر العرض مطلوب").transform(Number),
    ])
    .refine((val) => Number.isFinite(val) && val >= 0, "السعر يجب أن يكون رقماً موجباً"),

  duration_days: z
    .union([z.number(), z.string().min(1).transform(Number)])
    .refine((val) => Number.isInteger(val) && val > 0, "المدة يجب أن تكون رقماً صحيحاً موجباً")
    .optional()
    .nullable(),

  start_date: z.string().optional().nullable().or(z.literal("")),
  end_date: z.string().optional().nullable().or(z.literal("")),

  is_active: z.boolean().default(true),

  plans: z
    .array(z.union([z.number(), z.string().transform(Number)]))
    .min(1, "يجب تحديد خطة أو فعالية واحدة على الأقل في العرض"),
});

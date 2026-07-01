import { z } from "zod";
import "./zodErrorMap";

export const branchSchema = z.object({
  club_id: z
    .number({ invalid_type_error: "يرجى اختيار النادي" })
    .positive("يرجى اختيار النادي")
    .or(z.string().min(1, "يرجى اختيار النادي").transform(Number)),

  name_ar: z
    .string({ required_error: "الاسم بالعربية مطلوب" })
    .min(2, "الاسم يجب أن يكون حرفين على الأقل")
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف"),
    
  name_en: z
    .string()
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف")
    .optional()
    .or(z.literal("")),

  gender_restriction: z.enum(["mixed", "male", "female"], { 
    required_error: "يرجى تحديد قيود الجنس" 
  }),

  type: z
    .string()
    .max(50, "نوع الفرع طويل جداً")
    .optional()
    .or(z.literal("")),

  phone: z
    .string()
    .max(15, "رقم الهاتف طويل جداً")
    .optional()
    .or(z.literal("")),

  country_code: z
    .string()
    .optional()
    .or(z.literal("")),

  address: z
    .string()
    .max(255, "العنوان طويل جداً")
    .optional()
    .or(z.literal("")),
    
  is_active: z.boolean().optional(),
});

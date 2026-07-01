import { z } from "zod";
import "./zodErrorMap";

export const activitySchema = z.object({
  name_ar: z
    .string({ required_error: "الاسم بالعربية مطلوب" })
    .min(2, "الاسم يجب أن يكون حرفين على الأقل")
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف"),
    
  name_en: z
    .string()
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف")
    .optional()
    .or(z.literal("")),

  description: z
    .string()
    .max(500, "الوصف طويل جداً")
    .optional()
    .or(z.literal("")),

  type: z.enum(["group_class", "personal_training", "facility_use", "workshop"], { 
    required_error: "يرجى تحديد نوع النشاط" 
  }),

  default_capacity: z
    .number()
    .positive("يجب أن تكون السعة أكبر من صفر")
    .or(z.string().min(1, "السعة مطلوبة").transform(val => Number(val) || 0)),

  is_private_equipment: z
    .number()
    .or(z.string().transform(val => Number(val))),

  gender_allowed: z.enum(["mixed", "male", "female"], { 
    required_error: "يرجى تحديد قيود الجنس" 
  }),

  is_active: z.boolean().optional(),
});

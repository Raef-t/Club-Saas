import { z } from "zod";
import "./zodErrorMap";

export const coachSchema = z.object({
  full_name: z
    .string({ required_error: "الاسم الكامل مطلوب" })
    .min(3, "الاسم يجب أن يكون 3 أحرف على الأقل")
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف"),
  
  gender: z.enum(["male", "female"], { required_error: "يرجى تحديد الجنس" }),
  
  dob: z
    .string()
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

  email: z
    .string()
    .max(100, "البريد الإلكتروني طويل جداً")
    .email("البريد الإلكتروني غير صالح")
    .optional()
    .or(z.literal("")),

  address: z
    .string()
    .max(255, "العنوان طويل جداً")
    .optional()
    .or(z.literal("")),

  branch_id: z
    .number({ invalid_type_error: "يرجى تحديد الفرع" })
    .positive("يرجى تحديد الفرع")
    .or(z.string().min(1, "يرجى تحديد الفرع").transform(Number)),

  specialization: z
    .string()
    .max(100, "التخصص طويل جداً")
    .optional()
    .or(z.literal("")),

  experience_years: z
    .number()
    .nonnegative("لا يمكن أن تكون سنوات الخبرة سالبة")
    .or(z.string().transform(val => Number(val) || 0)),

  employment_type: z.enum(["fixed_salary", "commission", "hourly", "hybrid"], {
    required_error: "يرجى تحديد نوع التوظيف",
  }),

  base_salary: z
    .number()
    .nonnegative("لا يمكن أن يكون الراتب سالباً")
    .or(z.string().transform(val => Number(val) || 0)),

  is_active: z.boolean().optional(),
});

import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonSchema } from "./modificationReasonSchema";

export const coachSchema = z.object({
  full_name: z
    .string({ required_error: "الاسم الكامل مطلوب" })
    .min(3, "الاسم يجب أن يكون 3 أحرف على الأقل")
    .max(100, "الاسم يجب ألا يتجاوز 100 حرف"),

  gender: z.enum(["male", "female"], { required_error: "يرجى تحديد الجنس" }),

  dob: z.string().optional().or(z.literal("")),

  phone: z.string().max(15, "رقم الهاتف طويل جداً").optional().or(z.literal("")),

  country_code: z.string().optional().or(z.literal("")),

  email: z
    .string()
    .max(100, "البريد الإلكتروني طويل جداً")
    .email("البريد الإلكتروني غير صالح")
    .optional()
    .or(z.literal("")),

  address: z.string().max(255, "العنوان طويل جداً").optional().or(z.literal("")),

  branch_id: z
    .number({ invalid_type_error: "يرجى تحديد الفرع" })
    .positive("يرجى تحديد الفرع")
    .or(z.string().min(1, "يرجى تحديد الفرع").transform(Number)),

  specialization: z.string().max(100, "التخصص طويل جداً").optional().or(z.literal("")),

  experience_years: z
    .number()
    .nonnegative("لا يمكن أن تكون سنوات الخبرة سالبة")
    .or(z.string().transform((val) => Number(val) || 0)),

  employment_type: z.enum(["fixed_salary", "commission", "commission_based", "hourly", "hybrid"], {
    required_error: "يرجى تحديد نوع التوظيف",
  }),

  base_salary: z
    .number()
    .nonnegative("لا يمكن أن يكون الراتب سالباً")
    .or(z.string().transform((val) => Number(val) || 0)),

  work_status: z.enum(["active", "suspended", "on_leave"]).optional(),
  is_active: z.boolean().optional(),
});

export const coachFormSchema = z.object({
  first_name: z
    .string({ required_error: "الاسم الأول مطلوب" })
    .trim()
    .min(1, "الاسم الأول مطلوب")
    .max(50, "الاسم الأول يجب ألا يتجاوز 50 حرفاً"),
  last_name: z
    .string({ required_error: "اسم العائلة مطلوب" })
    .trim()
    .min(1, "اسم العائلة مطلوب")
    .max(50, "اسم العائلة يجب ألا يتجاوز 50 حرفاً"),
  gender: z.enum(["male", "female"], {
    message: "يرجى تحديد الجنس",
  }),
  dob: z.string().optional().or(z.literal("")),
  phone_number: z
    .string()
    .trim()
    .max(15, "رقم الهاتف يجب ألا يتجاوز 15 رقماً")
    .regex(/^\d*$/, "رقم الهاتف يجب أن يحتوي على أرقام فقط")
    .optional()
    .or(z.literal("")),
  country_code: z.string().trim().min(1, "رمز الدولة مطلوب").optional().or(z.literal("")),
  address: z
    .string()
    .trim()
    .max(255, "العنوان يجب ألا يتجاوز 255 حرف")
    .optional()
    .or(z.literal("")),
  branch_ids: z.array(z.number().positive()).min(1, "يرجى اختيار فرع واحد على الأقل"),
  experience_years: z
    .number()
    .nonnegative("سنوات الخبرة لا يمكن أن تكون سالبة")
    .or(z.string().transform((val) => Number(val) || 0)),
  start_date: z.string().optional().or(z.literal("")),
  employment_type: z.enum(["fixed_salary", "commission", "commission_based", "hourly", "hybrid"], {
    message: "نوع التوظيف غير صالح",
  }),
  base_salary: z
    .number()
    .nonnegative("لا يمكن أن يكون الراتب سالباً")
    .or(z.string().transform((val) => Number(val) || 0)),
  default_commission_rate: z
    .number()
    .min(0, "نسبة العمولة لا يمكن أن تكون سالبة")
    .max(100, "نسبة العمولة لا يمكن أن تتجاوز 100%")
    .or(z.string().transform((val) => Number(val) || 0)),
  work_types: z
    .array(z.enum(["equipment", "activities"]))
    .optional()
    .default([]),
  activity_ids: z.array(z.number().positive()).optional().default([]),
  shifts: z.array(z.number().positive()).optional().default([]),
  work_status: z.enum(["active", "suspended", "on_leave"], {
    message: "يرجى اختيار حالة عمل صالحة",
  }),
  is_active: z.boolean().optional(),
});

export const coachUpdateFormSchema = coachFormSchema.extend({
  reason: modificationReasonSchema,
});

export const coachEditSchema = z.object({
  specialization: z
    .string()
    .trim()
    .max(100, "التخصص يجب ألا يتجاوز 100 حرف")
    .optional()
    .or(z.literal("")),

  experience_years: z
    .number()
    .nonnegative("سنوات الخبرة لا يمكن أن تكون سالبة")
    .or(z.string().transform((val) => Number(val) || 0)),

  employment_type: z.enum(["fixed_salary", "commission", "commission_based", "hourly", "hybrid"], {
    required_error: "يرجى تحديد نوع التوظيف",
  }),

  base_salary: z
    .number()
    .nonnegative("لا يمكن أن يكون الراتب سالباً")
    .or(z.string().transform((val) => Number(val) || 0)),

  work_status: z.enum(["active", "suspended", "on_leave"]).optional(),
  is_active: z.boolean().optional(),
});

import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonSchema } from "./modificationReasonSchema";

const staffRoles = [
  "admin",
  "management_admin",
  "manager",
  "coach",
  "receptionist",
  "cleaner",
  "staff",
];

export const staffFormSchema = z.object({
  first_name: z
    .string()
    .trim()
    .min(1, "الاسم الأول مطلوب")
    .max(50, "الاسم الأول يجب ألا يتجاوز 50 حرفًا"),
  last_name: z
    .string()
    .trim()
    .min(1, "اسم العائلة مطلوب")
    .max(50, "اسم العائلة يجب ألا يتجاوز 50 حرفًا"),
  country_code: z.string().trim().optional().or(z.literal("")),
  phone_number: z
    .string()
    .trim()
    .min(1, "رقم الهاتف مطلوب")
    .max(15, "رقم الهاتف يجب ألا يتجاوز 15 رقمًا")
    .regex(/^\d+$/, "رقم الهاتف يجب أن يحتوي على أرقام فقط"),
  role: z
    .string()
    .trim()
    .min(1, "يرجى اختيار دور وظيفي صالح"),
  employment_type: z.enum(["fixed_salary", "commission_based", "hybrid"], {
    message: "يرجى اختيار نوع توظيف صالح",
  }),
  base_salary: z
    .number()
    .nonnegative("الراتب الأساسي لا يمكن أن يكون سالبًا")
    .or(z.string().transform((value) => Number(value) || 0)),
  work_status: z.enum(["active", "suspended", "on_leave"], {
    message: "يرجى اختيار حالة عمل صالحة",
  }),
  is_active: z.boolean().optional(),
  start_date: z.string().optional().or(z.literal("")),
  start_time: z
    .string()
    .regex(/^([01]\d|2[0-3]):[0-5]\d$/, "يرجى إدخال موعد قدوم صالح")
    .optional()
    .or(z.literal("")),
  end_time: z
    .string()
    .regex(/^([01]\d|2[0-3]):[0-5]\d$/, "يرجى إدخال موعد مغادرة صالح")
    .optional()
    .or(z.literal("")),
  address: z
    .string()
    .trim()
    .max(255, "العنوان يجب ألا يتجاوز 255 حرفًا")
    .optional()
    .or(z.literal("")),
  branch_ids: z.array(z.number().positive()).min(1, "يرجى اختيار فرع واحد على الأقل"),
});

export const staffUpdateFormSchema = staffFormSchema.extend({
  reason: modificationReasonSchema,
});

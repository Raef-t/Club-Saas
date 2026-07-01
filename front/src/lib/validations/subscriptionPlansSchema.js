import { z } from "zod";
import "./zodErrorMap";

export const subscriptionPlanSchema = z.object({
  name: z
    .string({ required_error: "اسم الخطة مطلوب" })
    .min(2, "اسم الخطة يجب أن يكون حرفين على الأقل")
    .max(100, "اسم الخطة طويل جداً"),

  duration_in_days: z
    .number({ invalid_type_error: "مدة الخطة مطلوبة" })
    .positive("المدة يجب أن تكون أكبر من صفر")
    .or(z.string().min(1, "المدة مطلوبة").transform(Number)),

  price: z
    .number({ invalid_type_error: "السعر مطلوب" })
    .nonnegative("لا يمكن أن يكون السعر سالباً")
    .or(z.string().min(1, "السعر مطلوب").transform(Number)),
});

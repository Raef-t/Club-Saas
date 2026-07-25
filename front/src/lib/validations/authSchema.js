import { z } from "zod";
import "./zodErrorMap";

export const loginSchema = z.object({
  username: z
    .string({ required_error: "اسم المستخدم مطلوب" })
    .trim()
    .min(3, "يجب أن يتكون اسم المستخدم من 3 أحرف على الأقل")
    .max(50, "يجب ألا يتجاوز اسم المستخدم 50 حرفاً")
    .regex(/^[a-zA-Z0-9_.\-@]+$/, "اسم المستخدم يحتوي على أحرف غير مسموح بها"),

  password: z
    .string({ required_error: "كلمة المرور مطلوبة" })
    .min(6, "يجب أن تتكون كلمة المرور من 6 أحرف على الأقل")
    .max(100, "يجب ألا تتجاوز كلمة المرور 100 حرف"),

  remember: z.boolean().optional(),
});

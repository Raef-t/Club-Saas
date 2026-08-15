import { z } from "zod";
import "./zodErrorMap";

export const subscriptionPlanSuspensionSchema = z
  .object({
    suspend_start_date: z.string().min(1, "تاريخ بداية الإيقاف مطلوب"),
    suspend_end_date: z.string().min(1, "تاريخ نهاية الإيقاف مطلوب"),
    reason: z
      .string()
      .trim()
      .min(3, "يرجى كتابة سبب الإيقاف")
      .max(500, "سبب الإيقاف يجب ألا يتجاوز 500 حرف"),
  })
  .refine((data) => data.suspend_end_date >= data.suspend_start_date, {
    message: "تاريخ نهاية الإيقاف يجب ألا يسبق تاريخ البداية",
    path: ["suspend_end_date"],
  });

import { z } from "zod";
import "./zodErrorMap";

export const subscriptionSchema = z.object({
  member_id: z
    .number({ invalid_type_error: "يرجى اختيار العضو" })
    .positive("يرجى اختيار العضو")
    .or(z.string().min(1, "يرجى اختيار العضو").transform(Number)),

  plan_id: z
    .number({ invalid_type_error: "يرجى اختيار الخطة" })
    .positive("يرجى اختيار الخطة")
    .or(z.string().min(1, "يرجى اختيار الخطة").transform(Number)),

  paid_amount: z
    .number()
    .nonnegative("المبلغ المدفوع يجب أن يكون صفراً أو أكثر")
    .or(z.string().min(1, "المبلغ المدفوع مطلوب").transform(Number)),

  start_date: z
    .string({ required_error: "تاريخ بداية الاشتراك مطلوب" })
    .min(1, "تاريخ بداية الاشتراك مطلوب"),
});


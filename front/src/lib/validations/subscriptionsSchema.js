import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonSchema } from "./modificationReasonSchema";

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

  receipt_number: z
    .string({ required_error: "رقم الإيصال مطلوب" })
    .trim()
    .min(1, "رقم الإيصال مطلوب")
    .max(100, "رقم الإيصال يجب ألا يتجاوز 100 حرف"),

  start_date: z
    .string({ required_error: "تاريخ بداية الاشتراك مطلوب" })
    .min(1, "تاريخ بداية الاشتراك مطلوب"),

  end_date: z
    .string({ required_error: "تاريخ نهاية الاشتراك مطلوب" })
    .min(1, "تاريخ نهاية الاشتراك مطلوب"),
});

export const subscriptionEditSchema = z
  .object({
    member_id: z.coerce.number().positive("يرجى اختيار العضو"),
    plan_id: z.coerce.number().positive("يرجى اختيار الخطة"),
    offer_id: z.union([z.coerce.number().positive("رقم العرض غير صالح"), z.null()]).optional(),
    months_count: z.coerce.number().int().positive("عدد الأشهر يجب أن يكون واحداً أو أكثر"),
    start_date: z.string().min(1, "تاريخ بداية الاشتراك مطلوب"),
    end_date: z.string().min(1, "تاريخ نهاية الاشتراك مطلوب"),
    status: z.enum(["active", "expired", "cancelled", "frozen", "pending"], {
      message: "حالة الاشتراك غير صالحة",
    }),
    paid_amount: z.coerce.number().nonnegative("المبلغ المدفوع يجب أن يكون صفراً أو أكثر"),
    notes: z.string().max(1000, "الملاحظات يجب ألا تتجاوز 1000 حرف").optional(),
    reason: modificationReasonSchema,
  })
  .refine((data) => data.end_date >= data.start_date, {
    message: "تاريخ النهاية يجب ألا يسبق تاريخ البداية",
    path: ["end_date"],
  });

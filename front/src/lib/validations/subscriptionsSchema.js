import { z } from "zod";
import "./zodErrorMap";
import { modificationReasonSchema } from "./modificationReasonSchema";

const optionalReceiptSchema = z
  .string()
  .trim()
  .max(100, "رقم الإيصال يجب ألا يتجاوز 100 حرف")
  .optional();

export const subscriptionSchema = z
  .object({
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

    months_count: z.coerce.number().int().positive("عدد الأشهر يجب أن يكون واحداً أو أكثر"),

    receipt_number: optionalReceiptSchema,
    coach_receipt_number: optionalReceiptSchema,
    branch_receipt_number: optionalReceiptSchema,
    is_private_plan: z.boolean().optional().default(false),

    start_date: z
      .string({ required_error: "تاريخ بداية الاشتراك مطلوب" })
      .min(1, "تاريخ بداية الاشتراك مطلوب"),

    end_date: z
      .string({ required_error: "تاريخ نهاية الاشتراك مطلوب" })
      .min(1, "تاريخ نهاية الاشتراك مطلوب"),
  })
  .superRefine((data, ctx) => {
    const requiredReceipts = data.is_private_plan
      ? [
          ["coach_receipt_number", data.coach_receipt_number, "رقم إيصال الكوتش مطلوب"],
          ["branch_receipt_number", data.branch_receipt_number, "رقم إيصال النادي مطلوب"],
        ]
      : [["receipt_number", data.receipt_number, "رقم الإيصال مطلوب"]];

    requiredReceipts.forEach(([field, value, message]) => {
      if (!value) {
        ctx.addIssue({ code: z.ZodIssueCode.custom, path: [field], message });
      }
    });
  })
  .transform(({ is_private_plan, ...data }) => {
    const normalizedData = { ...data };

    if (is_private_plan) {
      // The API keeps the general receipt as an alias of the branch receipt,
      // while the UI only asks the user for the two real private-plan receipts.
      normalizedData.receipt_number = normalizedData.branch_receipt_number;
    } else {
      delete normalizedData.coach_receipt_number;
      delete normalizedData.branch_receipt_number;
    }

    return normalizedData;
  });

export const subscriptionEditSchema = z
  .object({
    member_id: z.coerce.number().positive("يرجى اختيار العضو"),
    plan_id: z.coerce.number().positive("يرجى اختيار الخطة"),
    offer_id: z.union([z.coerce.number().positive("رقم العرض غير صالح"), z.null()]).optional(),
    months_count: z.coerce.number().int().positive("عدد الأشهر يجب أن يكون واحداً أو أكثر"),
    start_date: z.string().min(1, "تاريخ بداية الاشتراك مطلوب"),
    end_date: z.string().min(1, "تاريخ نهاية الاشتراك مطلوب"),
    status: z.enum(["active", "finished", "frozen", "terminated"], {
      message: "حالة الاشتراك غير صالحة",
    }),
    paid_amount: z.coerce.number().nonnegative("المبلغ المدفوع يجب أن يكون صفراً أو أكثر"),
    payment_method: z.literal("cash"),
    receipt_number: optionalReceiptSchema,
    coach_receipt_number: optionalReceiptSchema,
    branch_receipt_number: optionalReceiptSchema,
    coach_paid_amount: z.coerce
      .number()
      .nonnegative("مبلغ الكوتش يجب أن يكون صفراً أو أكثر")
      .optional(),
    branch_paid_amount: z.coerce
      .number()
      .nonnegative("مبلغ النادي يجب أن يكون صفراً أو أكثر")
      .optional(),
    notes: z.string().max(1000, "الملاحظات يجب ألا تتجاوز 1000 حرف").optional(),
    reason: modificationReasonSchema,
  })
  .refine((data) => data.end_date >= data.start_date, {
    message: "تاريخ النهاية يجب ألا يسبق تاريخ البداية",
    path: ["end_date"],
  });

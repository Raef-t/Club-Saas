import { z } from "zod";
import "./zodErrorMap";
import { toIsoDate } from "@/components/forms/datePickerUtils";

export const lockerSchema = z.object({
  locker_number: z
    .string({ required_error: "رقم الخزانة مطلوب" })
    .min(1, "رقم الخزانة مطلوب")
    .max(50, "رقم الخزانة طويل جداً"),
  key_number: z.string().optional().nullable(),
  branch_id: z
    .number({ invalid_type_error: "الفرع مطلوب" })
    .positive("الفرع مطلوب")
    .or(z.string().min(1, "الفرع مطلوب").transform(Number)),
});

export const updateLockerSchema = z.object({
  locker_number: z
    .string({ required_error: "رقم الخزانة مطلوب" })
    .min(1, "رقم الخزانة مطلوب")
    .max(50, "رقم الخزانة طويل جداً"),
  key_number: z.string().optional().nullable(),
  status: z.string({ required_error: "الحالة مطلوبة" }).min(1, "الحالة مطلوبة"),
  holder_type: z.string().optional(),
  holder_id: z.union([z.number(), z.string().transform(Number)]).optional(),
  holder_name: z.string().optional(),
});

export const reserveLockerSchema = z
  .object({
    reservation_type: z.enum(["rental", "assign"], {
      error: "نوع الحجز مطلوب",
    }),
    holder_type: z.enum(["member", "coach", "staff"], {
      error: "نوع المستفيد يجب أن يكون لاعباً أو كوتشاً أو موظفاً",
    }),
    holder_id: z.union([
      z.number({ invalid_type_error: "رقم المستفيد مطلوب" }).positive("رقم المستفيد مطلوب"),
      z.string().min(1, "رقم المستفيد مطلوب").transform(Number),
    ]),
    price: z
      .union([
        z
          .number({ invalid_type_error: "السعر مطلوب" })
          .min(0, "السعر يجب أن يكون أكبر من أو يساوي الصفر"),
        z.string().min(1, "السعر مطلوب").transform(Number),
      ])
      .optional(),
    start_date: z.string().optional(),
    end_date: z.string().optional(),
  })
  .superRefine((data, ctx) => {
    if (data.holder_type !== "coach" && (!data.start_date || !data.start_date.trim())) {
      ctx.addIssue({
        code: "custom",
        message: "تاريخ البداية مطلوب",
        path: ["start_date"],
      });
    }
  });

export const initialLockerForm = {
  locker_number: "",
  key_number: "",
  branch_id: "",
};

export const initialUpdateLockerForm = {
  locker_number: "",
  key_number: "",
  status: "available",
  holder_type: "",
  holder_id: "",
  holder_name: "",
};

export function createInitialReserveLockerForm(date = new Date()) {
  const today = toIsoDate(date);
  return {
    reservation_type: "assign",
    holder_type: "member",
    holder_id: "",
    price: "",
    start_date: today,
    end_date: today,
  };
}

export const initialReserveLockerForm = {
  reservation_type: "assign",
  holder_type: "member",
  holder_id: "",
  price: "",
  start_date: "",
  end_date: "",
};

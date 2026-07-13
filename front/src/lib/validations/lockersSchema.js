import { z } from "zod";
import "./zodErrorMap";

export const lockerSchema = z.object({
  locker_number: z
    .string({ required_error: "رقم الخزانة مطلوب" })
    .min(1, "رقم الخزانة مطلوب")
    .max(50, "رقم الخزانة طويل جداً"),
  branch_id: z
    .number({ invalid_type_error: "الفرع مطلوب" })
    .positive("الفرع مطلوب")
    .or(z.string().min(1, "الفرع مطلوب").transform(Number)),
});

export const initialLockerForm = {
  locker_number: "",
  branch_id: "",
};

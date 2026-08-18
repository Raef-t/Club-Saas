import { z } from "zod";
import "./zodErrorMap";

export const modificationReasonSchema = z
  .string({ required_error: "سبب التعديل مطلوب" })
  .trim()
  .min(1, "سبب التعديل مطلوب");

export const modificationReasonObjectSchema = z.object({
  reason: modificationReasonSchema,
});

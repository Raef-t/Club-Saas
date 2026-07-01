import { z } from "zod";

export const customErrorMap = (issue, ctx) => {
  if (issue.code === z.ZodIssueCode.invalid_type) {
    if (issue.expected === "string") return { message: "يجب أن يكون نصاً" };
    if (issue.expected === "number") return { message: "يجب أن يكون رقماً" };
    return { message: "قيمة غير صالحة" };
  }
  if (issue.code === z.ZodIssueCode.too_small) {
    if (issue.type === "string") return { message: `يجب أن يحتوي على الأقل ${issue.minimum} حرف/أحرف` };
    if (issue.type === "number") return { message: `يجب أن يكون أكبر من أو يساوي ${issue.minimum}` };
  }
  if (issue.code === z.ZodIssueCode.too_big) {
    if (issue.type === "string") return { message: `يجب ألا يتجاوز ${issue.maximum} حرف/أحرف` };
    if (issue.type === "number") return { message: `يجب ألا يتجاوز ${issue.maximum}` };
  }
  if (issue.code === z.ZodIssueCode.custom) {
    return { message: issue.message || "قيمة غير صالحة" };
  }
  if (issue.message) {
    return { message: issue.message };
  }
  return { message: "هذا الحقل مطلوب أو غير صالح" };
};

z.setErrorMap(customErrorMap);

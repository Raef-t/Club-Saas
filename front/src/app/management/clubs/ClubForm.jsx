"use client";

import { useState } from "react";
import Button from "@/components/ui/Button";
import { CheckboxField } from "@/components/forms/CheckboxField";
import { Field } from "@/components/forms/FormControls";
import { UploadBox } from "@/components/forms/UploadBox";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { clubSchema } from "@/lib/validations/clubsSchema";
import { createClubFormValues, createClubPayload } from "./clubUtils";

/**
 * Renders and validates the create and edit form for a club.
 */
export default function ClubForm({
  mode,
  initialValues,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
}) {
  const [form, setForm] = useState(() => createClubFormValues(initialValues));
  const [errors, setErrors] = useState({});

  /**
   * Updates one form field and clears its stale validation error.
   */
  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => {
      if (!current[field]) return current;
      const updated = { ...current };
      delete updated[field];
      return updated;
    });
  }

  /**
   * Validates the form and submits the normalized backend payload.
   */
  function handleSubmit(event) {
    event.preventDefault();
    const validation = clubSchema.safeParse({
      name: form.name.trim(),
      logo: form.logo,
      logo_url: form.logo_url ? form.logo_url.trim() : "",
      is_active: Boolean(form.is_active),
    });

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    setErrors({});
    onSubmit(createClubPayload(form));
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      <Field
        label="اسم النادي"
        value={form.name}
        onChange={(event) => updateField("name", event.target.value)}
        placeholder="تكنوجيم"
        required
        type="text"
        error={errors.name}
      />

      <UploadBox
        label="صورة الشعار"
        subtitle="jpeg, png, jpg, webp, svg (الحد الأقصى 2 ميجابايت)"
        accept=".jpeg,.png,.jpg,.webp,.svg"
        maxSizeMB={2}
        multiple={false}
        value={form.logo ? [form.logo] : []}
        onChange={(files) => updateField("logo", files[0] || null)}
      />

      <CheckboxField
        label="تفعيل النادي"
        checked={form.is_active}
        onChange={(event) => updateField("is_active", event.target.checked)}
      />

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}>
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button
          type="submit"
          className="h-11 flex-1 !text-black font-semibold"
          loading={isLoading}
        >
          {mode === "edit" ? "حفظ التعديل" : "إنشاء النادي"}
        </Button>
      </div>
    </form>
  );
}

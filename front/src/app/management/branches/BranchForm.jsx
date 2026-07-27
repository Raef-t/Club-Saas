"use client";

import { useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { Field, PhoneField } from "@/components/forms/FormControls";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { branchSchema } from "@/lib/validations/branchesSchema";
import { BRANCH_GENDER_OPTIONS } from "./branchConstants";
import { createBranchFormValues, createBranchPayload, createClubOptions } from "./branchUtils";

/**
 * Renders and validates the create and edit form for a branch.
 */
export default function BranchForm({
  mode,
  initialValues,
  clubs = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "flex min-h-full flex-col",
}) {
  const [form, setForm] = useState(() => createBranchFormValues(initialValues, clubs[0]?.id));
  const [errors, setErrors] = useState({});
  const clubOptions = useMemo(() => createClubOptions(clubs), [clubs]);

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
    const validation = branchSchema.safeParse({
      ...form,
      club_id: Number(form.club_id),
      name_ar: form.name_ar.trim(),
      address: form.address.trim(),
      country_code: form.country_code.trim(),
      phone: form.phone.trim(),
      type: "gym",
    });

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    setErrors({});
    onSubmit(createBranchPayload(form));
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      <div className="flex-1 space-y-4 pb-4">
        <label className="block text-right text-sm text-app-muted-light">
          النادي التابع له
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.club_id}
            onChange={(value) => updateField("club_id", value)}
            options={clubOptions}
            placeholder="اختر النادي"
            error={errors.club_id}
          />
        </label>

        <Field
          label="اسم الفرع بالعربية"
          value={form.name_ar}
          onChange={(event) => updateField("name_ar", event.target.value)}
          placeholder="مثال: الفرع الرئيسي"
          required
          type="text"
          error={errors.name_ar}
        />

        <label className="block text-right text-sm text-app-muted-light">
          التقييد الجنسي
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.gender_restriction}
            onChange={(value) => updateField("gender_restriction", value)}
            options={BRANCH_GENDER_OPTIONS}
            placeholder="اختر التقييد الجنسي"
            error={errors.gender_restriction}
          />
        </label>

        <Field
          label="العنوان"
          value={form.address}
          onChange={(event) => updateField("address", event.target.value)}
          placeholder="مثال: حلب، الشهباء"
          required={false}
          type="text"
          error={errors.address}
        />

        <PhoneField
          label="رقم الهاتف"
          phoneValue={form.phone}
          onPhoneChange={(value) => updateField("phone", value)}
          codeValue={form.country_code}
          onCodeChange={(value) => updateField("country_code", value)}
          required={false}
          error={errors.phone || errors.country_code}
        />
      </div>

      {errorMessage && (
        <p className="mb-4 rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div
        className={`${showFooterActions ? "sticky flex" : "entry-form-actions-hidden"} -bottom-5 z-10 -mx-5 -mb-5 mt-4 items-center gap-3 border-t border-app-line bg-app-bg p-5`}
      >
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          {mode === "edit" ? "حفظ التعديل" : "إنشاء الفرع"}
        </Button>
      </div>
    </form>
  );
}

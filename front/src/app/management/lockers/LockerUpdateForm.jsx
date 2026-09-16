"use client";

import { useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import { updateLockerSchema } from "@/lib/validations/lockersSchema";
import { LOCKER_EDIT_STATUS_OPTIONS } from "./lockerConstants";
import {
  createLockerUpdateInitialValues,
  createLockerUpdatePayload,
  getLockerValidationErrors,
} from "./lockerUtils";

/**
 * Collects and validates locker number, key and status updates.
 */
export default function LockerUpdateForm({
  formId,
  initialData,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState(() => createLockerUpdateInitialValues(initialData));
  const [errors, setErrors] = useState({});

  /**
   * Updates one field and clears its validation error.
   */
  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));

    if (errors[field]) {
      setErrors((current) => ({ ...current, [field]: null }));
    }
  }

  /**
   * Validates and submits a normalized locker update payload.
   */
  function handleSubmit(event) {
    event.preventDefault();
    const validation = updateLockerSchema.safeParse(createLockerUpdatePayload(form));

    if (!validation.success) {
      setErrors(getLockerValidationErrors(validation.error));
      return;
    }

    setErrors({});
    onSubmit(validation.data);
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className="flex flex-col gap-5">
      {errorMessage && (
        <div
          className="rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-sm text-app-red"
          role="alert"
        >
          {errorMessage}
        </div>
      )}

      <div className="flex flex-col gap-4">
        <Field
          label="رقم الخزانة"
          type="text"
          required
          value={form.locker_number}
          onChange={(event) => updateField("locker_number", event.target.value)}
          error={errors.locker_number}
          dir="ltr"
          maxLength={50}
        />

        <Field
          label="رقم المفتاح"
          type="text"
          value={form.key_number}
          onChange={(event) => updateField("key_number", event.target.value)}
          error={errors.key_number}
          dir="ltr"
          maxLength={50}
        />

        <div className="flex flex-col gap-1.5 text-start">
          <label className="flex items-center gap-1 text-sm font-medium text-white">
            الحالة <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={LOCKER_EDIT_STATUS_OPTIONS}
            value={form.status}
            onChange={(value) => updateField("status", value)}
            error={errors.status}
          />
        </div>

        <ModificationReasonField
          value={form.reason}
          onChange={(value) => updateField("reason", value)}
          error={errors.reason}
        />
      </div>

      <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
        <Button type="button" tone="ghost" onClick={onCancel} disabled={isLoading}>
          إلغاء
        </Button>
        <Button type="submit" loading={isLoading}>
          حفظ التعديلات
        </Button>
      </div>
    </form>
  );
}

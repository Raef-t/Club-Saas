"use client";

import { useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import { updateLockerSchema } from "@/lib/validations/lockersSchema";
import {
  LOCKER_HOLDER_TYPE_OPTIONS,
  LOCKER_OCCUPIED_STATUSES,
  LOCKER_STATUS_OPTIONS,
} from "./lockerConstants";
import LockerHolderField from "./LockerHolderField";
import {
  createLockerMemberOptions,
  createLockerUpdateInitialValues,
  createLockerUpdatePayload,
  getLockerValidationErrors,
} from "./lockerUtils";

/**
 * Collects and validates locker status and holder updates.
 */
export default function LockerUpdateForm({
  formId,
  initialData,
  members,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState(() => createLockerUpdateInitialValues(initialData));
  const [errors, setErrors] = useState({});
  const memberOptions = useMemo(() => createLockerMemberOptions(members), [members]);
  const canHaveHolder = LOCKER_OCCUPIED_STATUSES.includes(form.status);

  /**
   * Updates one field and keeps holder values consistent with status changes.
   */
  function updateField(field, value) {
    setForm((current) => {
      if (field === "status" && !LOCKER_OCCUPIED_STATUSES.includes(value)) {
        return {
          ...current,
          status: value,
          holder_type: "",
          holder_id: "",
          holder_name: "",
        };
      }

      if (field === "holder_type") {
        return {
          ...current,
          holder_type: value,
          holder_id: "",
          holder_name: "",
        };
      }

      return { ...current, [field]: value };
    });

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

        <div className="flex flex-col gap-1.5 text-start">
          <label className="flex items-center gap-1 text-sm font-medium text-white">
            الحالة <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={LOCKER_STATUS_OPTIONS}
            value={form.status}
            onChange={(value) => updateField("status", value)}
            error={errors.status}
          />
        </div>

        {canHaveHolder && (
          <>
            <div className="flex flex-col gap-1.5 text-start">
              <label className="text-sm font-medium text-white">نوع المستفيد (اختياري)</label>
              <Dropdown
                options={LOCKER_HOLDER_TYPE_OPTIONS}
                value={form.holder_type}
                onChange={(value) => updateField("holder_type", value)}
              />
            </div>

            <LockerHolderField
              holderType={form.holder_type}
              holderId={form.holder_id}
              memberOptions={memberOptions}
              onChange={(value) => updateField("holder_id", value)}
              error={errors.holder_id}
            />

            {form.holder_type && (
              <Field
                label="اسم المستفيد (اختياري)"
                type="text"
                required={false}
                value={form.holder_name}
                onChange={(event) => updateField("holder_name", event.target.value)}
                error={errors.holder_name}
              />
            )}
          </>
        )}
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

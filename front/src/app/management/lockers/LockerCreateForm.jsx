"use client";

import { useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import { LockerIcon } from "@/components/icons/Icons";
import { initialLockerForm, lockerSchema } from "@/lib/validations/lockersSchema";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getPreferredBranchId } from "@/lib/managementBranchUtils";
import { createLockerBranchOptions, getLockerValidationErrors } from "./lockerUtils";

/**
 * Collects and validates the fields required to create a locker.
 */
export default function LockerCreateForm({
  formId,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  branches,
  showFooterActions = true,
}) {
  const { selectedBranchId } = useManagementBranch();
  const [form, setForm] = useState(() => ({
    ...initialLockerForm,
    branch_id: getPreferredBranchId({
      currentBranchId: initialLockerForm.branch_id,
      selectedBranchId,
      branches,
    }),
  }));
  const [errors, setErrors] = useState({});
  const branchOptions = useMemo(() => createLockerBranchOptions(branches), [branches]);

  /**
   * Updates one form value and clears its previous validation error.
   */
  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) {
      setErrors((current) => ({ ...current, [field]: null }));
    }
  }

  /**
   * Validates and submits a normalized locker creation payload.
   */
  function handleSubmit(event) {
    event.preventDefault();
    const validation = lockerSchema.safeParse({
      locker_number: form.locker_number.trim(),
      branch_id: Number(form.branch_id),
    });

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

      <div className="grid gap-5 md:grid-cols-2">
        <div className="flex flex-col gap-1.5 text-start">
          <label className="flex items-center gap-1 text-sm font-medium text-white">
            الفرع <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={[{ value: "", label: "اختر الفرع..." }, ...branchOptions]}
            value={form.branch_id}
            onChange={(value) => updateField("branch_id", value)}
            error={errors.branch_id}
          />
        </div>

        <Field
          label="رقم الخزانة"
          type="text"
          required
          icon={LockerIcon}
          placeholder="L-001"
          value={form.locker_number}
          onChange={(event) => updateField("locker_number", event.target.value)}
          error={errors.locker_number}
          dir="ltr"
          maxLength={50}
        />
      </div>

      {showFooterActions && (
        <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
          <Button type="button" tone="ghost" onClick={onCancel} disabled={isLoading}>
            إلغاء
          </Button>
          <Button type="submit" loading={isLoading}>
            حفظ
          </Button>
        </div>
      )}
    </form>
  );
}

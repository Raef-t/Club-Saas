"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getPreferredBranchId, getGenderForBranchId } from "@/lib/managementBranchUtils";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { activitySchema, activityUpdateSchema } from "@/lib/validations/activitiesSchema";
import { GENDER_OPTIONS } from "./activityConstants";
import {
  createActivityFormValues,
  createActivityOptions,
  createActivityPayload,
} from "./activityUtils";

/**
 * Renders and validates the create and edit form for an activity.
 */
export default function ActivityForm({
  mode,
  initialValues,
  branches = [],
  activityTypes = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
}) {
  const { selectedBranchId } = useManagementBranch();
  const [form, setForm] = useState(() => {
    const values = createActivityFormValues(initialValues);
    const isCreate = mode !== "edit";

    return {
      ...values,
      branch_id: getPreferredBranchId({
        currentBranchId: values.branch_id,
        selectedBranchId,
        branches,
      }),
      ...(isCreate && {
        gender_allowed: getGenderForBranchId(branches, selectedBranchId, values.gender_allowed),
      }),
    };
  });
  const [errors, setErrors] = useState({});
  useEffect(() => {
    setForm((current) => ({
      ...current,
      gender_allowed: getGenderForBranchId(branches, current.branch_id, current.gender_allowed),
    }));
  }, [branches, form.branch_id]);
  const branchOptions = useMemo(() => createActivityOptions(branches), [branches]);
  const typeOptions = useMemo(() => createActivityOptions(activityTypes), [activityTypes]);

  /**
   * Updates one form field and clears its stale validation error.
   */
  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
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
    const schema = mode === "edit" ? activityUpdateSchema : activitySchema;
    const validation = schema.safeParse({
      ...form,
      name: form.name.trim(),
      description: form.description.trim(),
    });

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    setErrors({});
    onSubmit(createActivityPayload(form, mode === "edit"));
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      <Field
        label="اسم النشاط"
        value={form.name}
        onChange={(event) => updateField("name", event.target.value)}
        placeholder="مثال: صالة حديد حرة أو يوغا"
        required
        type="text"
        error={errors.name}
      />

      <ActivityDropdown
        label="الفرع التابع له"
        value={form.branch_id}
        onChange={(value) => updateField("branch_id", value)}
        options={branchOptions}
        placeholder="اختر الفرع"
        error={errors.branch_id}
      />

      <ActivityDropdown
        label="نوع الفئة / التصنيف"
        value={form.activity_type_id}
        onChange={(value) => updateField("activity_type_id", value)}
        options={typeOptions}
        placeholder="اختر نوع الفئة"
        error={errors.activity_type_id}
      />

      <TextAreaField
        label="الوصف"
        value={form.description}
        onChange={(event) => updateField("description", event.target.value)}
        placeholder="أدخل وصفاً مختصراً للنشاط والتمارين..."
        error={errors.description}
      />

      <ActivityDropdown
        label="الجمهور المستهدف"
        value={form.gender_allowed}
        onChange={(value) => updateField("gender_allowed", value)}
        options={GENDER_OPTIONS}
        placeholder="اختر الفئة المسموح بها"
        error={errors.gender_allowed}
      />

      <label className="flex items-center gap-3 pt-2">
        <input
          type="checkbox"
          checked={form.is_active}
          onChange={(event) => updateField("is_active", event.target.checked)}
          className="peer sr-only"
        />
        <span className="relative h-6 w-11 cursor-pointer rounded-full bg-app-line after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-app-yellow peer-checked:after:-translate-x-[18px]" />
        <span className="text-sm font-medium text-white">نشط في النظام</span>
      </label>

      {mode === "edit" && (
        <ModificationReasonField
          value={form.reason}
          onChange={(value) => updateField("reason", value)}
          error={errors.reason}
        />
      )}

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}>
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          {mode === "edit" ? "حفظ التعديل" : "إنشاء النشاط"}
        </Button>
      </div>
    </form>
  );
}

/**
 * Renders a labeled dropdown used by the activity editor.
 */
function ActivityDropdown({ label, value, onChange, options, placeholder, error }) {
  return (
    <label className="block text-right text-sm text-app-muted-light">
      {label}
      <Dropdown
        className="mt-2 text-white"
        buttonClassName="h-11 bg-app-card-soft"
        value={value}
        onChange={onChange}
        options={options}
        placeholder={placeholder}
        error={error}
      />
    </label>
  );
}

"use client";

import { useEffect, useMemo, useState } from "react";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Dropdown from "@/components/ui/Dropdown";
import { Field, TextAreaField } from "@/components/forms/FormControls";
import { useGetBranchShiftsQuery } from "@/lib/api/branchesApi";
import { genderLabels } from "@/lib/constants";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  getPreferredBranchId,
  getGenderForBranchId,
} from "@/lib/managementBranchUtils";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { activitySchema } from "@/lib/validations/activitiesSchema";
import { DAYS_OF_WEEK, GENDER_OPTIONS, SHIFT_ACTIVITY_TYPE_IDS } from "./activityConstants";
import {
  createActivityFormValues,
  createActivityOptions,
  createActivityPayload,
  getActivityCollection,
} from "./activityUtils";

/**
 * Renders and validates the create and edit form for an activity.
 */
export default function ActivityForm({
  mode,
  initialValues,
  initialShifts,
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
      gender_allowed: getGenderForBranchId(
        branches,
        current.branch_id,
        current.gender_allowed,
      ),
    }));
  }, [branches, form.branch_id]);
  const branchId = Number(form.branch_id);
  const initialBranchId = Number(initialValues?.branch_id || initialValues?.branch?.id);
  const {
    currentData: shiftsResponse,
    isLoading: isLoadingShifts,
    isFetching: isFetchingShifts,
  } = useGetBranchShiftsQuery(branchId, { skip: !branchId });
  const branchShifts = useMemo(
    () =>
      getActivityCollection(
        shiftsResponse || (branchId === initialBranchId ? initialShifts : undefined),
      ),
    [branchId, initialBranchId, initialShifts, shiftsResponse],
  );
  const showShifts = SHIFT_ACTIVITY_TYPE_IDS.has(Number(form.activity_type_id));
  const branchOptions = useMemo(() => createActivityOptions(branches), [branches]);
  const typeOptions = useMemo(() => createActivityOptions(activityTypes), [activityTypes]);

  /**
   * Updates one form field and clears its stale validation error.
   */
  function updateField(field, value) {
    setForm((current) => ({
      ...current,
      [field]: value,
      ...(field === "branch_id" ? { shifts: [] } : {}),
    }));
    setErrors((current) => {
      if (!current[field]) return current;
      const updated = { ...current };
      delete updated[field];
      return updated;
    });
  }

  /**
   * Adds or removes one shift from the selected activity shifts.
   */
  function toggleShift(shiftId) {
    const normalizedId = Number(shiftId);
    const isSelected = form.shifts.includes(normalizedId);
    const shifts = isSelected
      ? form.shifts.filter((id) => id !== normalizedId)
      : [...form.shifts, normalizedId];

    updateField("shifts", shifts);
  }

  /**
   * Validates the form and submits the normalized backend payload.
   */
  function handleSubmit(event) {
    event.preventDefault();
    const validation = activitySchema.safeParse({
      ...form,
      name: form.name.trim(),
      description: form.description.trim(),
      shifts: form.shifts.map(Number),
    });

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    setErrors({});
    onSubmit(createActivityPayload(form, showShifts));
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

      {showShifts && (
        <ActivityShifts
          shifts={branchShifts}
          selectedShiftIds={form.shifts}
          isLoading={isLoadingShifts || isFetchingShifts}
          onToggle={toggleShift}
        />
      )}

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

/**
 * Renders the selectable shifts available for the chosen branch.
 */
function ActivityShifts({ shifts, selectedShiftIds, isLoading, onToggle }) {
  const { formatTime } = useTimeFormat();

  return (
    <div className="block text-right text-sm text-app-muted-light">
      الورديات / الشفتات المتاحة
      <div className="mt-2 grid max-h-48 grid-cols-2 gap-3 overflow-y-auto rounded-lg border border-app-line bg-app-card-soft p-3">
        {isLoading && shifts.length === 0 ? (
          <p className="col-span-2 py-2 text-center text-xs text-app-muted-light">
            جاري تحميل الورديات...
          </p>
        ) : shifts.length === 0 ? (
          <p className="col-span-2 py-2 text-center text-xs text-app-muted-light">
            لا توجد ورديات مسجلة لهذا الفرع
          </p>
        ) : (
          shifts.map((shift) => {
            const shiftId = Number(shift.id);
            const startTime = formatTime(shift.start_time) || "";
            const endTime = formatTime(shift.end_time) || "";
            const gender = genderLabels[shift.gender_allowed] || shift.gender_allowed || "مختلط";
            const label = `${DAYS_OF_WEEK[shift.day_of_week] || "يوم غير معروف"} | من ${startTime} إلى ${endTime} (${gender})`;

            return (
              <Checkbox
                key={shift.id}
                label={label}
                checked={selectedShiftIds.includes(shiftId)}
                onChange={() => onToggle(shiftId)}
              />
            );
          })
        )}
      </div>
    </div>
  );
}

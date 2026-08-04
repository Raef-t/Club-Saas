"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Dropdown from "@/components/ui/Dropdown";
import PhoneField from "@/components/forms/PhoneField";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { TrashIcon } from "@/components/icons/Icons";
import { useGetBranchSettingsQuery, useGetBranchShiftsQuery } from "@/lib/api/branchesApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import { getGenderForBranchId } from "@/lib/managementBranchUtils";
import { coachFormSchema } from "@/lib/validations/coachesSchema";
import { CURRENCY_SYMBOL } from "@/lib/utils";
import { EMPLOYMENT_TYPES as employmentTypes, SHIFT_GENDER_LABELS as shiftGenderLabels } from "./coachConstants";
import { createCoachFormInitialValues, getEmploymentTypeForWorkTypes, calculateAge } from "./coachFormUtils";

export function CoachCreateForm({
  formId,
  branches = [],
  activities = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  showFooterActions = true,
  initialValues = null,
}) {
  const { selectedBranchId } = useManagementBranch();
  const { formatTime } = useTimeFormat();
  const [form, setForm] = useState(() => {
    const values = createCoachFormInitialValues(initialValues, branches, selectedBranchId);
    if (!initialValues) {
      values.gender = getGenderForBranchId(branches, values.branch_ids?.[0], values.gender);
    }
    return values;
  });

  const calculatedAge = calculateAge(form.dob);

  const branchId1 = form.branch_ids?.[0];
  const branchId2 = form.branch_ids?.[1];
  const branchId3 = form.branch_ids?.[2];
  useEffect(() => {
    setForm((current) => ({
      ...current,
      gender: getGenderForBranchId(branches, current.branch_ids?.[0], current.gender),
    }));
  }, [branchId1, branches]);

  const { data: shiftsResponse1, isFetching: isLoadingShifts1 } = useGetBranchShiftsQuery(
    branchId1,
    { skip: !branchId1 },
  );
  const { data: shiftsResponse2, isFetching: isLoadingShifts2 } = useGetBranchShiftsQuery(
    branchId2,
    { skip: !branchId2 },
  );
  const { data: shiftsResponse3, isFetching: isLoadingShifts3 } = useGetBranchShiftsQuery(
    branchId3,
    { skip: !branchId3 },
  );

  const isLoadingShifts = isLoadingShifts1 || isLoadingShifts2 || isLoadingShifts3;

  const { data: branchSettingsRes } = useGetBranchSettingsQuery(branchId1, {
    skip: !branchId1,
  });
  const branchSettings = branchSettingsRes?.data;

  useEffect(() => {
    if (branchSettings && !initialValues) {
      setForm((prev) => ({
        ...prev,
        base_salary: branchSettings.default_employee_salary
          ? String(Number(branchSettings.default_employee_salary))
          : prev.base_salary,
        default_commission_rate: branchSettings.default_coach_commission_percentage
          ? String(Number(branchSettings.default_coach_commission_percentage))
          : prev.default_commission_rate,
      }));
    }
  }, [branchSettings, initialValues]);

  const branchShifts = useMemo(() => {
    const all = [];
    const ids = new Set();
    [shiftsResponse1, shiftsResponse2, shiftsResponse3].forEach((res) => {
      if (Array.isArray(res?.data)) {
        res.data.forEach((shift) => {
          if (!ids.has(shift.id)) {
            ids.add(shift.id);
            all.push(shift);
          }
        });
      }
    });
    return all;
  }, [shiftsResponse1, shiftsResponse2, shiftsResponse3]);

  const hasEquipmentActivity = useMemo(() => {
    if (Array.isArray(form.shift_ids) && form.shift_ids.length > 0) return true;
    if (Array.isArray(form.work_types) && form.work_types.includes("equipment")) return true;
    return activities.some((act) => {
      const isSelected = form.activity_ids.includes(Number(act.id));
      if (!isSelected) return false;
      const nameStr =
        typeof act.name === "object" ? act.name?.ar || act.name?.en || "" : act.name || "";
      return (
        nameStr.includes("أجهزة") ||
        nameStr.includes("عام") ||
        nameStr.toLowerCase().includes("equipment")
      );
    });
  }, [activities, form.activity_ids, form.work_types, form.shift_ids]);

  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => {
      const updated = { ...current, [field]: value };
      if (field === "branch_ids") {
        updated.shift_ids = [];
      }
      if (field === "work_types") {
        updated.employment_type = getEmploymentTypeForWorkTypes(value);
      }
      return updated;
    });
    if (errors && errors[field]) {
      setErrors((current) => ({ ...current, [field]: null }));
    }
  }

  function handleSubmit(event) {
    event.preventDefault();

    const result = coachFormSchema.safeParse(form);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        const pathKey = issue.path.join("_");
        if (!formattedErrors[pathKey]) {
          formattedErrors[pathKey] = issue.message;
        }
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    const shiftsPayload = hasEquipmentActivity ? (form.shift_ids || []).map(Number) : [];

    onSubmit({
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      gender: form.gender,
      dob: form.dob,
      phone_number: form.phone_number.trim() || null,
      country_code: form.country_code.trim() || "+963",
      address: form.address.trim() || null,
      branch_ids: form.branch_ids,
      experience_years: Number(form.experience_years) || 0,
      is_active: form.is_active,
      employment_type: form.employment_type,
      base_salary: Number(form.base_salary) || 0,
      default_commission_rate: Number(form.default_commission_rate) || 0,
      work_types: form.work_types,
      activity_ids: form.activity_ids,
      shifts: shiftsPayload,
    });
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <div className="grid grid-cols-2 gap-3">
        <CoachTextField
          label="الاسم الأول *"
          value={form.first_name}
          onChange={(value) => updateField("first_name", value)}
          error={errors.first_name}
          placeholder="الاسم الأول"
        />
        <CoachTextField
          label="اسم العائلة *"
          value={form.last_name}
          onChange={(value) => updateField("last_name", value)}
          error={errors.last_name}
          placeholder="اسم العائلة"
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label className="block text-right text-sm text-app-muted-light">
          الجنس *
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="bg-app-card-soft h-11"
            value={form.gender}
            onChange={(val) => updateField("gender", val)}
            options={[
              { value: "male", label: "ذكر" },
              { value: "female", label: "أنثى" },
            ]}
            error={errors && errors.gender}
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          تاريخ الميلاد
          <div className="mt-2">
            <DatePickerSmart
              value={form.dob}
              onChange={(value) => updateField("dob", value)}
              placeholder="DD/MM/YYYY"
              error={errors.dob}
            />
          </div>
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          العمر
          <input
            type="text"
            readOnly
            disabled
            value={calculatedAge !== null ? `${calculatedAge} سنة` : "يُحسب تلقائياً"}
            className="app-input mt-2 h-11 w-full bg-app-card-soft/60 px-3 text-right text-white font-medium outline-none border border-app-line/60 cursor-not-allowed"
            placeholder="يُحسب تلقائياً"
          />
        </label>
      </div>

      <div>
        <PhoneField
          label="رقم الهاتف"
          phoneValue={form.phone_number}
          onPhoneChange={(val) => updateField("phone_number", val)}
          codeValue={form.country_code}
          onCodeChange={(val) => updateField("country_code", val)}
          required={false}
          className="text-right w-full"
          error={errors && (errors.phone_number || errors.country_code)}
        />
      </div>

      <div className="block text-right text-sm text-app-muted-light">
        الفروع التابع لها *
        <div
          className={`mt-2 grid grid-cols-2 gap-2 p-3 bg-app-card-soft rounded-lg max-h-36 overflow-y-auto ${
            errors && errors.branch_ids ? "border border-app-red" : "border border-app-line"
          }`}
        >
          {branches.map((b) => {
            const checked = form.branch_ids.includes(Number(b.id));
            const bName = typeof b.name === "object" ? b.name?.ar || b.name?.en : b.name;
            return (
              <Checkbox
                key={b.id}
                label={bName}
                checked={checked}
                onChange={() => {
                  const id = Number(b.id);
                  const newIds = checked
                    ? form.branch_ids.filter((x) => x !== id)
                    : [...form.branch_ids, id];
                  updateField("branch_ids", newIds);
                }}
              />
            );
          })}
        </div>
        {errors && errors.branch_ids && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.branch_ids}
          </span>
        )}
      </div>

      <div className="block text-right text-sm text-app-muted-light">
        الأنشطة والرياضات المنسوبة للمدرب (اختياري)
        <div className="mt-2">
          <Dropdown
            options={activities
              .filter((act) => !form.activity_ids.includes(Number(act.id)))
              .map((act) => ({
                value: act.id,
                label: typeof act.name === "object" ? act.name?.ar || act.name?.en : act.name,
              }))}
            value={null}
            onChange={(val) => {
              const id = Number(val);
              if (id && !form.activity_ids.includes(id)) {
                updateField("activity_ids", [...form.activity_ids, id]);
              }
            }}
            placeholder="اختر نشاطاً لإضافته..."
            buttonClassName="h-11 border border-app-line bg-black/35 hover:border-app-yellow/50"
          />

          {form.activity_ids.length > 0 && (
            <div className="mt-3 flex flex-wrap gap-2 p-3 bg-app-card-soft rounded-lg border border-app-line">
              {form.activity_ids.map((id) => {
                const act = activities.find((a) => Number(a.id) === id);
                if (!act) return null;
                const actName =
                  typeof act.name === "object" ? act.name?.ar || act.name?.en : act.name;
                return (
                  <div
                    key={id}
                    className="flex items-center gap-2 bg-black/40 border border-app-line rounded-lg px-3 py-1.5"
                  >
                    <span className="text-xs text-white">{actName}</span>
                    <button
                      type="button"
                      onClick={() =>
                        updateField(
                          "activity_ids",
                          form.activity_ids.filter((x) => x !== id),
                        )
                      }
                      className="text-app-muted hover:text-app-red transition-colors"
                      title="إزالة"
                    >
                      <TrashIcon className="size-3.5" />
                    </button>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {hasEquipmentActivity && (
        <div className="block text-right text-sm text-app-muted-light">
          الورديات / الشفتات المتاحة
          <div className="mt-2 grid grid-cols-2 gap-3 p-3 bg-app-card-soft rounded-lg border border-app-line max-h-48 overflow-y-auto">
            {isLoadingShifts ? (
              <p className="text-xs text-app-muted-light text-center py-2 col-span-2">
                جاري تحميل الورديات...
              </p>
            ) : branchShifts.length === 0 ? (
              <p className="text-xs text-app-muted-light text-center py-2 col-span-2">
                لا توجد ورديات مسجلة للفروع المحددة
              </p>
            ) : (
              branchShifts.map((shift) => {
                const isChecked = form.shift_ids?.some((id) => Number(id) === Number(shift.id));
                const shiftNameStr = shift.name || "وردية بدون اسم";
                const startTime = shift.start_time ? formatTime(shift.start_time) : "";
                const endTime = shift.end_time ? formatTime(shift.end_time) : "";
                const gender =
                  shiftGenderLabels[shift.gender_allowed] || shift.gender_allowed || "مختلط";
                const label = `${shiftNameStr} | من ${startTime} إلى ${endTime} (${gender})`;

                return (
                  <Checkbox
                    key={shift.id}
                    label={label}
                    checked={isChecked}
                    onChange={() => {
                      const idNum = Number(shift.id);
                      const newShifts = isChecked
                        ? (form.shift_ids || []).filter((x) => Number(x) !== idNum)
                        : [...(form.shift_ids || []), idNum];
                      updateField("shift_ids", newShifts);
                    }}
                  />
                );
              })
            )}
          </div>
        </div>
      )}

      <div className="block text-right text-sm text-app-muted-light">
        أنواع عمل المدرب
        <div className="mt-2 flex items-center gap-6 p-3 bg-app-card-soft rounded-lg border border-app-line">
          <Checkbox
            label="أجهزة (equipment)"
            checked={form.work_types.includes("equipment")}
            onChange={() => {
              const checked = form.work_types.includes("equipment");
              const newTypes = checked
                ? form.work_types.filter((x) => x !== "equipment")
                : [...form.work_types, "equipment"];
              updateField("work_types", newTypes);
            }}
          />
          <Checkbox
            label="فعاليات/حصص (activities)"
            checked={form.work_types.includes("activities")}
            onChange={() => {
              const checked = form.work_types.includes("activities");
              const newTypes = checked
                ? form.work_types.filter((x) => x !== "activities")
                : [...form.work_types, "activities"];
              updateField("work_types", newTypes);
            }}
          />
        </div>
      </div>

      <div>
        <label className="block text-right text-sm text-app-muted-light">
          سنوات الخبرة
          <input
            value={form.experience_years}
            onChange={(event) => updateField("experience_years", event.target.value)}
            aria-invalid={Boolean(errors && errors.experience_years)}
            className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
              errors && errors.experience_years
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            type="number"
            min="0"
          />
          {errors && errors.experience_years && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.experience_years}
            </span>
          )}
        </label>

      </div>

      <div className="rounded-lg border border-app-line bg-app-card-soft p-3">
        <Checkbox
          label="المدرب نشط"
          checked={form.is_active}
          onChange={(event) => updateField("is_active", event.target.checked)}
        />
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        نوع التوظيف
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.employment_type}
          onChange={(val) => updateField("employment_type", val)}
          options={employmentTypes}
          disabled={true}
          error={errors && errors.employment_type}
        />
      </label>

      {form.employment_type !== "commission_based" && form.employment_type !== "commission" && (
        <label className="block text-right text-sm text-app-muted-light">
          الراتب الأساسي ({CURRENCY_SYMBOL}) *
          <input
            value={form.base_salary}
            onChange={(event) => updateField("base_salary", event.target.value)}
            aria-invalid={Boolean(errors && errors.base_salary)}
            className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
              errors && errors.base_salary
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            type="number"
            min="0"
            required
          />
          {errors && errors.base_salary && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.base_salary}
            </span>
          )}
        </label>
      )}

      {(form.employment_type === "commission_based" ||
        form.employment_type === "commission" ||
        form.employment_type === "hybrid") && (
        <label className="block text-right text-sm text-app-muted-light">
          نسبة العمولة الافتراضية للمدرب (%) *
          <input
            value={form.default_commission_rate}
            onChange={(event) => updateField("default_commission_rate", event.target.value)}
            aria-invalid={Boolean(errors && errors.default_commission_rate)}
            className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
              errors && errors.default_commission_rate
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            type="number"
            min="0"
            max="100"
            step="0.1"
            required
          />
          {errors && errors.default_commission_rate && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.default_commission_rate}
            </span>
          )}
        </label>
      )}

      <label className="block text-right text-sm text-app-muted-light">
        العنوان السكني
        <input
          value={form.address}
          onChange={(event) => updateField("address", event.target.value)}
          aria-invalid={Boolean(errors && errors.address)}
          className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
            errors && errors.address
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          placeholder="المدينة، الحي"
        />
        {errors && errors.address && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.address}
          </span>
        )}
      </label>

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className="flex gap-3 pt-2">
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          {initialValues ? "حفظ التعديل" : "إضافة المدرب"}
        </Button>
      </div>
    </form>
  );
}

function CoachTextField({ label, value, onChange, error, placeholder }) {
  return (
    <label className="block text-right text-sm text-app-muted-light">
      {label}
      <input
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-invalid={Boolean(error)}
        className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
          error
            ? "border border-app-red focus:border-app-red"
            : "focus:border-app-yellow/70"
        }`}
        placeholder={placeholder}
      />
      {error && (
        <span className="mt-1.5 block text-xs text-app-red" role="alert">
          {error}
        </span>
      )}
    </label>
  );
}

"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Dropdown from "@/components/ui/Dropdown";
import PhoneField from "@/components/forms/PhoneField";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import TimePickerSmart from "@/components/forms/TimePickerSmart";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import { useGetBranchSettingsQuery } from "@/lib/api/branchesApi";
import { useGetRolesQuery } from "@/lib/api/usersApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { staffFormSchema, staffUpdateFormSchema } from "@/lib/validations/staffSchema";
import { CURRENCY_SYMBOL, formatLocalizedName } from "@/lib/utils";
import {
  STAFF_EMPLOYMENT_OPTIONS,
  STAFF_ROLE_LABELS,
  STAFF_ROLE_OPTIONS,
  STAFF_WORK_STATUS_OPTIONS,
} from "./staffConstants";
import { createStaffInitialValues } from "./staffUtils";

function StaffTextField({ label, value, onChange, error, placeholder, type = "text", min }) {
  return (
    <label className="block text-right text-sm text-app-muted-light">
      {label}
      <input
        type={type}
        min={min}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        aria-invalid={Boolean(error)}
        className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-app-text outline-none transition focus:border-app-yellow/70 ${
          error ? "border-app-red bg-app-red/5" : "border-app-line"
        }`}
      />
      {error && (
        <span className="mt-1.5 block text-xs text-app-red" role="alert">
          {error}
        </span>
      )}
    </label>
  );
}

export default function StaffForm({
  formId,
  branches = [],
  initialValues = null,
  onSubmit,
  onCancel,
  isLoading = false,
  errorMessage = "",
}) {
  const { selectedBranchId } = useManagementBranch();
  const { data: rolesResponse } = useGetRolesQuery();
  const [form, setForm] = useState(() => {
    const defaults = createStaffInitialValues({ branches, selectedBranchId });
    return initialValues
      ? {
          ...defaults,
          ...initialValues,
          branch_ids: initialValues.branch_ids || defaults.branch_ids,
        }
      : defaults;
  });
  const [errors, setErrors] = useState({});
  const primaryBranchId = form.branch_ids[0];
  const { data: branchSettingsResponse } = useGetBranchSettingsQuery(primaryBranchId, {
    skip: !primaryBranchId,
  });

  useEffect(() => {
    if (initialValues) return;
    const defaultSalary = Number(branchSettingsResponse?.data?.default_employee_salary);
    if (!Number.isFinite(defaultSalary) || defaultSalary <= 0) return;
    setForm((current) =>
      Number(current.base_salary) > 0
        ? current
        : { ...current, base_salary: String(defaultSalary) },
    );
  }, [branchSettingsResponse, initialValues]);

  const roleOptions = useMemo(() => {
    const rawRoles = rolesResponse?.data?.roles || rolesResponse?.data || [];
    if (Array.isArray(rawRoles) && rawRoles.length > 0) {
      const filtered = rawRoles.filter((r) => r.is_visible !== false);
      const mapped = filtered.map((r) => ({
        value: r.name,
        label: r.name_ar || STAFF_ROLE_LABELS[r.name] || r.name,
      }));

      if (initialValues?.role && !mapped.some((opt) => opt.value === initialValues.role)) {
        mapped.push({
          value: initialValues.role,
          label: STAFF_ROLE_LABELS[initialValues.role] || initialValues.role,
        });
      }

      return mapped;
    }

    if (initialValues?.role === "coach") {
      return [...STAFF_ROLE_OPTIONS, { value: "coach", label: STAFF_ROLE_LABELS.coach }];
    }
    return STAFF_ROLE_OPTIONS;
  }, [rolesResponse, initialValues?.role]);

  useEffect(() => {
    if (initialValues) return;
    if (roleOptions.length > 0 && !roleOptions.some((opt) => opt.value === form.role)) {
      const preferred =
        roleOptions.find((opt) => opt.value === "reception" || opt.value === "receptionist") ||
        roleOptions[0];
      if (preferred) {
        setForm((current) => ({ ...current, role: preferred.value }));
      }
    }
  }, [roleOptions, initialValues, form.role]);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const schema = initialValues ? staffUpdateFormSchema : staffFormSchema;
    const result = schema.safeParse(form);

    if (!result.success) {
      const nextErrors = {};
      result.error.issues.forEach((issue) => {
        const key = issue.path.join("_");
        if (!nextErrors[key]) nextErrors[key] = issue.message;
      });
      setErrors(nextErrors);
      return;
    }

    setErrors({});
    onSubmit({
      ...result.data,
      first_name: result.data.first_name.trim(),
      last_name: result.data.last_name.trim(),
      phone_number: result.data.phone_number.trim(),
      country_code: result.data.country_code?.trim() || "+963",
      base_salary: Number(result.data.base_salary) || 0,
      address: result.data.address?.trim() || "",
      branch_ids: result.data.branch_ids.map(Number),
    });
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className="space-y-5" dir="rtl">
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <StaffTextField
          label="الاسم الأول *"
          value={form.first_name}
          onChange={(value) => updateField("first_name", value)}
          error={errors.first_name}
          placeholder="الاسم الأول"
        />
        <StaffTextField
          label="اسم العائلة *"
          value={form.last_name}
          onChange={(value) => updateField("last_name", value)}
          error={errors.last_name}
          placeholder="اسم العائلة"
        />
      </div>

      <PhoneField
        label="رقم الهاتف"
        phoneValue={form.phone_number}
        onPhoneChange={(value) => updateField("phone_number", value)}
        codeValue={form.country_code}
        onCodeChange={(value) => updateField("country_code", value)}
        required
        className="w-full text-right"
        error={errors.phone_number || errors.country_code}
      />

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-right text-sm text-app-muted-light">
          الدور الوظيفي *
          <Dropdown
            className="mt-2 text-app-text"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.role}
            onChange={(value) => updateField("role", value)}
            options={roleOptions}
            error={errors.role}
          />
        </label>
        <label className="block text-right text-sm text-app-muted-light">
          نوع التوظيف *
          <Dropdown
            className="mt-2 text-app-text"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.employment_type}
            onChange={(value) => updateField("employment_type", value)}
            options={STAFF_EMPLOYMENT_OPTIONS}
            error={errors.employment_type}
          />
        </label>
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <StaffTextField
          label={`الراتب الأساسي (${CURRENCY_SYMBOL})`}
          type="number"
          min="0"
          value={form.base_salary}
          onChange={(value) => updateField("base_salary", value)}
          error={errors.base_salary}
          placeholder="0"
        />
        <label className="block text-right text-sm text-app-muted-light">
          تاريخ المباشرة
          <div className="mt-2">
            <DatePickerSmart
              value={form.start_date}
              onChange={(value) => updateField("start_date", value)}
              placeholder="DD/MM/YYYY"
              error={errors.start_date}
            />
          </div>
        </label>
      </div>

      <div className="block text-right text-sm text-app-muted-light">
        الفروع التابع لها *
        <div
          className={`mt-2 grid max-h-40 grid-cols-1 gap-3 overflow-y-auto rounded-lg bg-app-card-soft p-3 sm:grid-cols-2 ${
            errors.branch_ids ? "border border-app-red" : "border border-app-line"
          }`}
        >
          {branches.length ? (
            branches.map((branch) => {
              const id = Number(branch.id);
              const checked = form.branch_ids.includes(id);
              return (
                <Checkbox
                  key={branch.id}
                  label={formatLocalizedName(branch.name)}
                  checked={checked}
                  onChange={() =>
                    updateField(
                      "branch_ids",
                      checked
                        ? form.branch_ids.filter((branchId) => branchId !== id)
                        : [...form.branch_ids, id],
                    )
                  }
                />
              );
            })
          ) : (
            <p className="text-xs text-app-muted-light">لا توجد فروع متاحة.</p>
          )}
        </div>
        {errors.branch_ids && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.branch_ids}
          </span>
        )}
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-right text-sm text-app-muted-light">
          موعد القدوم
          <div className="mt-2">
            <TimePickerSmart
              value={form.start_time}
              onChange={(value) => updateField("start_time", value)}
              placeholder="HH:MM"
              required={false}
              error={errors.start_time}
            />
          </div>
        </label>
        <label className="block text-right text-sm text-app-muted-light">
          موعد المغادرة
          <div className="mt-2">
            <TimePickerSmart
              value={form.end_time}
              onChange={(value) => updateField("end_time", value)}
              placeholder="HH:MM"
              required={false}
              error={errors.end_time}
            />
          </div>
        </label>
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        العنوان
        <textarea
          rows={3}
          value={form.address}
          onChange={(event) => updateField("address", event.target.value)}
          placeholder="عنوان الموظف"
          aria-invalid={Boolean(errors.address)}
          className={`app-input mt-2 min-h-24 w-full resize-y bg-app-card-soft px-3 py-3 text-right text-app-text outline-none transition focus:border-app-yellow/70 ${
            errors.address ? "border-app-red bg-app-red/5" : "border-app-line"
          }`}
        />
        {errors.address && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.address}
          </span>
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        حالة العمل *
        <Dropdown
          className="mt-2 text-app-text"
          buttonClassName="h-11 bg-app-card-soft"
          value={form.work_status}
          onChange={(value) => updateField("work_status", value)}
          options={STAFF_WORK_STATUS_OPTIONS}
          error={errors.work_status}
        />
      </label>

      {initialValues && (
        <ModificationReasonField
          value={form.reason}
          onChange={(value) => updateField("reason", value)}
          error={errors.reason}
        />
      )}

      {errorMessage && (
        <p
          className="rounded-lg border border-app-red/40 bg-app-red/10 p-3 text-sm text-app-red"
          role="alert"
        >
          {errorMessage}
        </p>
      )}

      <div className="flex flex-col-reverse gap-3 border-t border-app-line pt-5 sm:flex-row">
        <Button
          type="button"
          tone="outline"
          className="flex-1"
          onClick={onCancel}
          disabled={isLoading}
        >
          إلغاء
        </Button>
        <Button type="submit" className="flex-1 text-black" loading={isLoading}>
          {initialValues ? "حفظ التعديلات" : "إضافة الموظف"}
        </Button>
      </div>
    </form>
  );
}

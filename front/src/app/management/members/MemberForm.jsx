"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import PhoneField from "@/components/forms/PhoneField";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { memberSchema } from "@/lib/validations/membersSchema";
import { CURRENCY_SYMBOL, formatLocalizedName } from "@/lib/utils";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  getPreferredBranchId,
  getGenderForBranchId,
} from "@/lib/managementBranchUtils";
import {
  MEMBER_INITIAL_FORM as initialForm,
  RELATION_OPTIONS as relationOptions,
} from "./memberConstants";

function calculateAgeFromDob(dobString) {
  if (!dobString) return undefined;
  const birthDate = new Date(dobString);
  if (isNaN(birthDate.getTime())) return undefined;
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  return age >= 0 ? age : undefined;
}

export { MEMBER_INITIAL_FORM as memberInitialForm } from "./memberConstants";
export function MemberForm({
  mode,
  initialValues = initialForm,
  branches = [],
  plans = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
  submitLabel,
}) {
  const { selectedBranchId } = useManagementBranch();
  const [form, setForm] = useState(() => {
    const isCreate = mode !== "edit";
    return {
      ...initialValues,
      branch_id: getPreferredBranchId({
        currentBranchId: initialValues.branch_id,
        selectedBranchId,
        branches,
      }),
      ...(isCreate && {
        gender: getGenderForBranchId(branches, selectedBranchId, initialValues.gender),
      }),
    };
  });
  const [errors, setErrors] = useState({});
  useEffect(() => {
    setForm((current) => ({
      ...current,
      gender: getGenderForBranchId(branches, current.branch_id, current.gender),
    }));
  }, [branches, form.branch_id]);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();

    const additional_contacts = form.emergency_name.trim()
      ? [
          {
            name: form.emergency_name.trim(),
            relation: form.emergency_relation,
            country_code: form.emergency_country_code.trim(),
            phone_number: form.emergency_phone.trim(),
          },
        ]
      : [];

    const dobValue = form.dob ? form.dob.trim() : "";
    const computedAge = calculateAgeFromDob(dobValue);

    const validationData = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      mobile_country_code: form.mobile_country_code.trim(),
      mobile: form.mobile.trim(),
      gender: form.gender,
      dob: dobValue,
      age: computedAge,
      branch_id: Number(form.branch_id),
      emergency_name: form.emergency_name.trim(),
      emergency_relation: form.emergency_relation,
      emergency_phone: form.emergency_phone.trim(),
      emergency_phone: form.emergency_phone.trim(),
      emergency_country_code: form.emergency_country_code.trim(),
    };

    const result = memberSchema.safeParse(validationData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});

    if (mode === "create") {
      onSubmit({
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        mobile_country_code: form.mobile_country_code.trim(),
        mobile: form.mobile.trim(),
        gender: form.gender,
        dob: dobValue,
        age: computedAge,
        branch_id: Number(form.branch_id),
        additional_contacts,
      });
    } else {
      onSubmit({
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        mobile_country_code: form.mobile_country_code.trim(),
        mobile: form.mobile.trim(),
        gender: form.gender,
        dob: dobValue,
        age: computedAge,
        branch_id: Number(form.branch_id),
        additional_contacts,
      });
    }
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      <div className="grid grid-cols-2 gap-3">
        <label className="block text-right text-sm text-app-muted-light">
          الاسم الأول
          <input
            value={form.first_name}
            onChange={(event) => updateField("first_name", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="مثال: أحمد"
            required
          />
          {errors && errors.first_name && (
            <span className="text-app-red text-xs mt-1 block text-right w-full">
              {errors.first_name}
            </span>
          )}
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          اسم العائلة
          <input
            value={form.last_name}
            onChange={(event) => updateField("last_name", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="مثال: محمد"
            required
          />
          {errors && errors.last_name && (
            <span className="text-app-red text-xs mt-1 block text-right w-full">
              {errors.last_name}
            </span>
          )}
        </label>
      </div>

      <PhoneField
        label="رقم الهاتف المحمول"
        phoneValue={form.mobile}
        onPhoneChange={(val) => updateField("mobile", val)}
        codeValue={form.mobile_country_code}
        onCodeChange={(val) => updateField("mobile_country_code", val)}
        required
        error={errors && (errors.mobile || errors.mobile_country_code)}
      />

      <div className="grid grid-cols-2 gap-3">
        <label className="block text-right text-sm text-app-muted-light">
          الجنس
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

        <div>
          <label className="block text-right text-sm text-app-muted-light mb-2">
            تاريخ الميلاد
          </label>
          <DatePickerSmart
            value={form.dob}
            onChange={(val) => updateField("dob", val)}
            placeholder="dd/mm/yyyy"
            error={errors && errors.dob}
          />
        </div>
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        الفرع
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.branch_id}
          onChange={(val) => updateField("branch_id", val)}
          options={branches.map((b) => ({
            value: String(b.id),
            label: formatLocalizedName(b.name),
          }))}
          placeholder="اختر الفرع"
          error={errors && errors.branch_id}
        />
      </label>

      {/* Emergency Contact */}
      <div className="border-t border-app-line pt-4 mt-2">
        <h4 className="text-sm font-semibold text-white mb-3">
          جهة اتصال إضافية للطوارئ (اختياري)
        </h4>

        <label className="block text-right text-sm text-app-muted-light mb-3">
          الاسم
          <input
            value={form.emergency_name}
            onChange={(event) => updateField("emergency_name", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="مثال: والد اللاعب"
          />
          {errors && errors.emergency_name && (
            <span className="text-app-red text-xs mt-1 block text-right w-full">
              {errors.emergency_name}
            </span>
          )}
        </label>

        <div className="grid grid-cols-2 gap-3 mb-3">
          <label className="block text-right text-sm text-app-muted-light">
            صلة القرابة
            <Dropdown
              className="mt-2 text-white"
              buttonClassName="bg-app-card-soft h-11"
              value={form.emergency_relation}
              onChange={(val) => updateField("emergency_relation", val)}
              options={relationOptions}
              error={errors && errors.emergency_relation}
            />
          </label>

          <PhoneField
            label="رقم الهاتف"
            phoneValue={form.emergency_phone}
            onPhoneChange={(val) => updateField("emergency_phone", val)}
            codeValue={form.emergency_country_code}
            onCodeChange={(val) => updateField("emergency_country_code", val)}
            required={false}
            error={errors && (errors.emergency_phone || errors.emergency_country_code)}
          />
        </div>
      </div>

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
          {submitLabel || (mode === "edit" ? "حفظ التعديل" : "إضافة العضو")}
        </Button>
      </div>
    </form>
  );
}

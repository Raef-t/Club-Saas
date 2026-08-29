"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Dropdown from "@/components/ui/Dropdown";
import PhoneField from "@/components/forms/PhoneField";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import { Field } from "@/components/forms/FormControls";
import { TrashIcon } from "@/components/icons/Icons";
import { useGetBranchSettingsQuery, useGetBranchShiftsQuery } from "@/lib/api/branchesApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useTimeFormat } from "@/lib/TimeFormatContext";
import { getGenderForBranchId } from "@/lib/managementBranchUtils";
import { getSettingsRecord } from "@/app/management/settings/settingsUtils";
import { coachFormSchema, coachUpdateFormSchema } from "@/lib/validations/coachesSchema";
import { CURRENCY_SYMBOL } from "@/lib/utils";
import { WORK_STATUS_OPTIONS } from "@/lib/workStatus";
import {
  EMPLOYMENT_TYPES as employmentTypes,
  SHIFT_GENDER_LABELS as shiftGenderLabels,
} from "./coachConstants";
import {
  COACH_ACTIVITY_KINDS,
  calculateAge,
  createCoachFormInitialValues,
  getComplementaryCommissionPercentage,
  getCoachActivityKind,
  getCoachRulesForActivities,
} from "./coachFormUtils";

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
  const lastBaseSalaryRef = useRef(form.base_salary);

  const calculatedAge = calculateAge(form.dob);
  const selectedActivities = useMemo(
    () =>
      activities.filter((activity) =>
        form.activity_ids.some((id) => Number(id) === Number(activity.id)),
      ),
    [activities, form.activity_ids],
  );
  const activityRules = useMemo(
    () => getCoachRulesForActivities(selectedActivities),
    [selectedActivities],
  );
  const showsCommissionFields = activityRules.hasRecognizedActivity
    ? activityRules.allowsCommission
    : form.employment_type === "commission_based" ||
      form.employment_type === "commission" ||
      form.employment_type === "hybrid";
  const hasStoredPrivateCommission = Boolean(
    initialValues &&
    form.work_types.includes("equipment") &&
    Number(form.private_commission_rate) > 0,
  );
  const showsPrivateCommission = activityRules.hasPrivateTraining || hasStoredPrivateCommission;
  const showsActivityCommission = activityRules.hasRecognizedActivity
    ? activityRules.hasGroupClass
    : form.work_types.length > 0
      ? form.work_types.includes("activities")
      : showsCommissionFields && !showsPrivateCommission;
  const privateCoachCommissionRate = showsPrivateCommission ? form.private_commission_rate : "";
  const activityClubCommissionRate = showsActivityCommission
    ? getComplementaryCommissionPercentage(form.default_commission_rate)
    : "";
  const selectableActivities = useMemo(
    () =>
      activities.filter((activity) => {
        if (form.activity_ids.includes(Number(activity.id))) return false;

        const kind = getCoachActivityKind(activity);
        if (kind === COACH_ACTIVITY_KINDS.DAILY_ENTRY) return false;
        return true;
      }),
    [activities, form.activity_ids],
  );

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

  const {
    currentData: branchSettingsRes,
    isFetching: isFetchingBranchSettings,
    error: branchSettingsError,
  } = useGetBranchSettingsQuery(branchId1, {
    skip: !branchId1,
  });
  const branchSettings = getSettingsRecord(branchSettingsRes);

  useEffect(() => {
    if (!activityRules.hasRecognizedActivity) return;

    setForm((current) => {
      let clubCommission = current.private_club_commission_rate;
      let activityCoachCommission = current.default_commission_rate;
      let privateCoachCommission = current.private_commission_rate;
      let baseSalary = current.base_salary;

      if (activityRules.allowsSalary) {
        if (!Number(baseSalary)) {
          const previousSalary = lastBaseSalaryRef.current;
          baseSalary = Number(previousSalary)
            ? previousSalary
            : branchSettings?.default_employee_salary
              ? String(Number(branchSettings.default_employee_salary))
              : baseSalary;
        }
        if (Number(baseSalary)) lastBaseSalaryRef.current = baseSalary;
      } else {
        if (Number(baseSalary)) lastBaseSalaryRef.current = baseSalary;
        baseSalary = "0";
      }

      if (activityRules.hasPrivateTraining) {
        if (
          String(clubCommission ?? "").trim() === "" &&
          branchSettings?.private_subscription_commission !== undefined
        ) {
          clubCommission = String(branchSettings.private_subscription_commission);
        }

        const calculatedPrivateCoachCommission =
          getComplementaryCommissionPercentage(clubCommission);
        if (calculatedPrivateCoachCommission !== "") {
          privateCoachCommission = calculatedPrivateCoachCommission;
        }
      }

      if (
        activityRules.hasGroupClass &&
        !initialValues &&
        !Number(activityCoachCommission) &&
        branchSettings?.default_coach_commission_percentage !== undefined
      ) {
        activityCoachCommission = String(
          Number(branchSettings.default_coach_commission_percentage),
        );
      }

      return {
        ...current,
        work_types: activityRules.workTypes,
        employment_type: activityRules.employmentType,
        base_salary: baseSalary,
        default_commission_rate: activityRules.hasGroupClass ? activityCoachCommission : "0",
        private_commission_rate: activityRules.hasPrivateTraining ? privateCoachCommission : "0",
        private_club_commission_rate: activityRules.hasPrivateTraining ? clubCommission : "",
        shifts: activityRules.allowsShifts ? current.shifts : [],
      };
    });
  }, [activityRules, branchSettings, initialValues]);

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

  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => {
      const updated = { ...current, [field]: value };
      if (field === "branch_ids" || field === "activity_ids") {
        updated.shifts = [];
        updated.private_club_commission_rate = "";
        updated.private_commission_rate = "0";
        updated.default_commission_rate = "0";
      }
      return updated;
    });
    if (errors && errors[field]) {
      setErrors((current) => ({ ...current, [field]: null }));
    }
  }

  function updatePrivateClubCommission(value) {
    setForm((current) => ({
      ...current,
      private_club_commission_rate: value,
      private_commission_rate: getComplementaryCommissionPercentage(value),
    }));
    setErrors((current) => ({
      ...current,
      private_club_commission_rate: null,
      private_commission_rate: null,
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();

    if (activityRules.hasDailyEntry) {
      setErrors({ activity_ids: "نشاط الدخول اليومي لا يُسند إلى مدرب." });
      return;
    }
    const normalizedForm = activityRules.hasRecognizedActivity
      ? {
          ...form,
          work_types: activityRules.workTypes,
          employment_type: activityRules.employmentType,
          base_salary: activityRules.allowsSalary ? form.base_salary : "0",
          default_commission_rate: activityRules.hasGroupClass ? form.default_commission_rate : "0",
          private_club_commission_rate: activityRules.hasPrivateTraining
            ? form.private_club_commission_rate
            : "0",
          private_commission_rate: activityRules.hasPrivateTraining
            ? form.private_commission_rate
            : "0",
          shifts: activityRules.allowsShifts ? form.shifts : [],
        }
      : form;
    const schema = initialValues ? coachUpdateFormSchema : coachFormSchema;
    const result = schema.safeParse(normalizedForm);
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
    const shiftsPayload = activityRules.allowsShifts
      ? (normalizedForm.shifts || []).map(Number)
      : [];

    onSubmit({
      first_name: normalizedForm.first_name.trim(),
      last_name: normalizedForm.last_name.trim(),
      gender: normalizedForm.gender,
      dob: normalizedForm.dob,
      phone_number: normalizedForm.phone_number.trim() || null,
      country_code: normalizedForm.country_code.trim() || "+963",
      address: normalizedForm.address.trim() || null,
      branch_ids: normalizedForm.branch_ids,
      experience_years: Number(normalizedForm.experience_years) || 0,
      start_date: normalizedForm.start_date || null,
      work_status: normalizedForm.work_status,
      is_active: normalizedForm.work_status === "active",
      employment_type: normalizedForm.employment_type,
      base_salary: Number(normalizedForm.base_salary) || 0,
      default_commission_rate: Number(normalizedForm.default_commission_rate) || 0,
      private_commission_rate: Number(normalizedForm.private_commission_rate) || 0,
      work_types: normalizedForm.work_types,
      activity_ids: normalizedForm.activity_ids,
      shifts: shiftsPayload,
      ...(initialValues ? { reason: result.data.reason } : {}),
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
            options={selectableActivities.map((act) => ({
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
            error={errors.activity_ids}
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
          <p className="mt-2 text-xs text-app-muted-light">
            أجهزة عام = راتب، والفعاليات أو أجهزة خاص = نسبة، والجمع بينهما = راتب ونسبة. أنشطة
            الدخول اليومي لا تُسند إلى مدرب.
          </p>
        </div>
      </div>

      {activityRules.allowsShifts && (
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
                const isChecked = form.shifts?.some((id) => Number(id) === Number(shift.id));
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
                        ? (form.shifts || []).filter((x) => Number(x) !== idNum)
                        : [...(form.shifts || []), idNum];
                      updateField("shifts", newShifts);
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
        <div className="mt-2 flex min-h-11 items-center gap-2 rounded-lg border border-app-line bg-app-card-soft p-3">
          {form.work_types.length > 0 ? (
            form.work_types.map((workType) => (
              <span
                key={workType}
                className="rounded-full bg-app-yellow/10 px-3 py-1 text-xs font-medium text-app-yellow"
              >
                {workType === "equipment" ? "أجهزة (equipment)" : "فعاليات/حصص (activities)"}
              </span>
            ))
          ) : (
            <span className="text-xs text-app-muted-light">
              اختر نشاطاً لتحديد نوع عمل المدرب تلقائياً
            </span>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
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

      <label className="block text-right text-sm text-app-muted-light">
        حالة العمل *
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="h-11 bg-app-card-soft"
          value={form.work_status}
          onChange={(value) => updateField("work_status", value)}
          options={WORK_STATUS_OPTIONS}
          error={errors.work_status}
        />
      </label>

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

      {(activityRules.hasRecognizedActivity
        ? activityRules.allowsSalary
        : form.employment_type !== "commission_based" && form.employment_type !== "commission") && (
        <label className="block text-right text-sm text-app-muted-light">
          الراتب الأساسي ({CURRENCY_SYMBOL}) *
          <input
            value={form.base_salary}
            onChange={(event) => {
              lastBaseSalaryRef.current = event.target.value;
              updateField("base_salary", event.target.value);
            }}
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

      {showsPrivateCommission && (
        <div className="rounded-xl border border-app-yellow/30 bg-app-yellow/5 p-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field
              label="نسبة النادي من التدريب الخاص (%)"
              type="number"
              min="0"
              max="100"
              step="0.01"
              value={form.private_club_commission_rate}
              onChange={(event) => updatePrivateClubCommission(event.target.value)}
              placeholder={isFetchingBranchSettings ? "جاري تحميل النسبة..." : "0"}
              disabled={isFetchingBranchSettings}
              error={errors.private_club_commission_rate}
            />
            <Field
              label="نسبة المدرب من التدريب الخاص (%)"
              type="number"
              value={privateCoachCommissionRate}
              onChange={() => {}}
              disabled
              required={false}
              error={errors.private_commission_rate}
            />
          </div>
          <p className="mt-3 text-right text-xs text-app-muted-light">
            تبدأ نسبة النادي من إعدادات الفرع، وتُحسب نسبة المدرب تلقائياً ليكون المجموع 100%.
          </p>
          {branchSettingsError && (
            <p className="mt-2 text-right text-xs text-app-red">
              تعذر تحميل النسبة الافتراضية، يمكنك إدخال نسبة النادي يدوياً.
            </p>
          )}
        </div>
      )}

      {showsActivityCommission && (
        <div className="rounded-xl border border-app-yellow/30 bg-app-yellow/5 p-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <Field
              label="نسبة النادي من الفعالية (%)"
              type="number"
              value={activityClubCommissionRate}
              onChange={() => {}}
              disabled
              required={false}
            />
            <Field
              label="نسبة المدرب من الفعالية (%)"
              type="number"
              min="0"
              max="100"
              step="0.1"
              value={form.default_commission_rate}
              onChange={(event) => updateField("default_commission_rate", event.target.value)}
              error={errors.default_commission_rate}
            />
          </div>
          <p className="mt-3 text-right text-xs text-app-muted-light">
            تبدأ نسبة المدرب من إعدادات الفرع، وتُحسب نسبة النادي تلقائياً ليكون المجموع 100%.
          </p>
          {branchSettingsError && (
            <p className="mt-2 text-right text-xs text-app-red">
              تعذر تحميل النسبة الافتراضية، يمكنك إدخال نسبة المدرب يدوياً.
            </p>
          )}
        </div>
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

      {initialValues && (
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
          error ? "border border-app-red focus:border-app-red" : "focus:border-app-yellow/70"
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

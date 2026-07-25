"use client";

import { useMemo, useState, useEffect } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import Dropdown from "@/components/ui/Dropdown";
import {
  PlusIcon,
  SearchIcon,
  FilterIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import Checkbox from "@/components/ui/Checkbox";
import { coachFormSchema, coachEditSchema } from "@/lib/validations/coachesSchema";
import { useCoaches } from "./useCoaches";
import PhoneField from "@/components/forms/PhoneField";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import {
  useGetBranchShiftsQuery,
  useGetBranchSettingsQuery,
} from "@/lib/api/branchesApi";
import { UploadBox } from "@/components/forms/UploadBox";
import { Field } from "@/components/forms/Field";

const daysOfWeek = {
  0: "الأحد",
  1: "الإثنين",
  2: "الثلاثاء",
  3: "الأربعاء",
  4: "الخميس",
  5: "الجمعة",
  6: "السبت",
};

const shiftGenderLabels = {
  mixed: "مختلط",
  male: "ذكور",
  female: "إناث",
};

const TABLE_GRID_COLUMNS = "minmax(160px,1.2fr) 120px 140px 120px 100px 100px";
const CURRENCY_SYMBOL = "ل.س";

const employmentTypes = [
  { value: "fixed_salary", label: "راتب ثابت" },
  { value: "commission_based", label: "نسبة فقط" },
  { value: "hybrid", label: "هجين (ثابت + نسبة)" },
];

const employmentLabels = {
  fixed_salary: "راتب ثابت",
  commission: "نسبة فقط",
  commission_based: "نسبة فقط",
  hybrid: "نظام هجين",
};

const genderLabels = {
  male: "ذكر",
  female: "أنثى",
};

function formatMoney(value) {
  const num = Number(value) || 0;
  return `${num.toLocaleString("en-US")} ${CURRENCY_SYMBOL}`;
}

function formatDate(value) {
  if (!value) return "-";
  const date = new Date(value);
  if (isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("ar", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(date);
}

function DetailItem({ label, value, tone = "default" }) {
  const toneClass =
    tone === "green"
      ? "text-app-green"
      : tone === "red"
        ? "text-app-red"
        : tone === "yellow"
          ? "text-app-yellow"
          : "text-app-text";

  return (
    <div className="rounded-lg border border-app-line bg-app-card-soft/70 p-3 text-right">
      <p className="text-[11px] text-app-muted-light">{label}</p>
      <p className={`mt-1 truncate text-sm font-medium ${toneClass}`}>
        {value ?? "-"}
      </p>
    </div>
  );
}

function CoachDetails({
  coach,
  branches = [],
  allActivities = [],
  isLoading,
  error,
  selectedActivityId,
  setSelectedActivityId,
  onAddActivity,
  onRemoveActivity,
  isAddingActivity,
  isRemovingActivity,
}) {
  if (isLoading) {
    return (
      <SkeletonPage
        blocks={[{ type: "details", sections: 2, itemsPerSection: 3 }]}
      />
    );
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل المدرب.
      </div>
    );
  }

  if (!coach) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل متوفرة.
      </div>
    );
  }

  const coachBranches = Array.isArray(coach.branch_ids) ? coach.branch_ids : [];
  const branchNames =
    coachBranches
      .map((id) => {
        const b = branches.find((item) => item.id === id);
        return b
          ? typeof b.name === "object"
            ? b.name?.ar || b.name?.en
            : b.name
          : `فرع #${id}`;
      })
      .join("، ") || "-";

  const coachActivities = coach.activities || [];
  const unassignedActivities = allActivities.filter(
    (act) => !coachActivities.some((ca) => ca.id === act.id),
  );

  return (
    <div className="space-y-6">
      {/* Basic Profile */}
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right flex items-center justify-between gap-4">
        <div>
          <h3 className="text-lg font-medium text-white">
            {coach.person?.full_name || "-"}
          </h3>
          <p className="mt-1 text-xs text-app-muted-light">
            كود الموظف: #{coach.id} | دور: {coach.role}
          </p>
        </div>
        <span
          className={`rounded px-2.5 py-1 text-xs font-semibold ${
            coach.is_active
              ? "bg-app-green/10 text-app-green"
              : "bg-app-red/10 text-app-red"
          }`}
        >
          {coach.is_active ? "نشط" : "غير نشط"}
        </span>
      </div>

      {/* Info Grid */}
      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="الفرع" value={branchNames} tone="yellow" />
        {/* <DetailItem
          label="التخصص"
          value={coach.details?.specialization || "غير محدد"}
        /> */}
        <DetailItem
          label="سنوات الخبرة"
          value={
            coach.details?.experience_years
              ? `${coach.details.experience_years} سنوات`
              : "-"
          }
        />
        <DetailItem
          label="الجنس"
          value={genderLabels[coach.person?.gender] || coach.person?.gender}
        />
        {/* <DetailItem
          label="تاريخ الميلاد"
          value={formatDate(coach.person?.dob)}
        /> */}
        <DetailItem
          label="العمر"
          value={coach.person?.age ? `${coach.person.age} سنة` : "-"}
        />
        <DetailItem
          label="نوع التوظيف"
          value={
            employmentLabels[coach.employment_type] || coach.employment_type
          }
        />
        <DetailItem
          label="الراتب الأساسي"
          value={formatMoney(coach.base_salary)}
          tone="green"
        />
        <DetailItem label="العنوان" value={coach.person?.address} />
        {/* <DetailItem label="البريد الإلكتروني" value={coach.person?.email} /> */}
        {/* <DetailItem label="رقم الهوية الوطنية" value={coach.person?.national_id} /> */}
        <DetailItem
          label="تاريخ الانضمام"
          value={formatDate(coach.created_at)}
        />
      </section>

      {/* Activities Section */}
      <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right space-y-4">
        <h4 className="text-sm font-semibold text-white">
          الأنشطة والرياضات المنسوبة للمدرب
        </h4>

        {coachActivities.length === 0 ? (
          <p className="text-xs text-app-muted-light">
            لم يتم إسناد أي أنشطة رياضية لهذا المدرب بعد.
          </p>
        ) : (
          <div className="space-y-2">
            {coachActivities.map((act) => (
              <div
                key={act.id}
                className="flex items-center justify-between p-2 rounded-lg bg-black/35 border border-app-line text-sm"
              >
                <button
                  type="button"
                  onClick={() => onRemoveActivity(act.id)}
                  disabled={isRemovingActivity}
                  className="text-app-red hover:bg-app-red/10 p-1.5 rounded transition disabled:opacity-50"
                  title="إلغاء إسناد النشاط"
                >
                  <TrashIcon className="size-4" />
                </button>
                <span className="font-medium text-app-text">
                  {typeof act.name === "string"
                    ? act.name
                    : act.name?.ar || act.name?.en || "-"}
                </span>
              </div>
            ))}
          </div>
        )}

        {/* Add Activity Controls */}
        {unassignedActivities.length > 0 && (
          <div className="pt-2 border-t border-app-line flex items-end gap-3">
            <div className="flex-1">
              <label className="block text-xs text-app-muted-light mb-1.5">
                إسناد نشاط جديد
              </label>
              <Dropdown
                className="text-white bg-black/40 rounded-lg text-sm"
                buttonClassName="h-10 border-app-line"
                value={selectedActivityId}
                onChange={setSelectedActivityId}
                options={unassignedActivities.map((act) => ({
                  value: act.id,
                  label:
                    typeof act.name === "string"
                      ? act.name
                      : act.name?.ar || act.name?.en || "",
                }))}
                placeholder="اختر نشاطاً رياضياً..."
              />
            </div>
            <Button
              type="button"
              className="h-10 px-4 text-sm font-medium"
              disabled={!selectedActivityId || isAddingActivity}
              loading={isAddingActivity}
              onClick={onAddActivity}
            >
              إسناد
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}

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
  const [form, setForm] = useState({
    full_name: initialValues?.full_name || "",
    gender: initialValues?.gender || "male",
    age: initialValues?.age || "",
    phone: initialValues?.phone || "",
    country_code: initialValues?.country_code || "+963",
    address: initialValues?.address || "",
    branch_ids:
      initialValues?.branch_ids ||
      (branches[0]?.id ? [Number(branches[0].id)] : []),
    specialization: initialValues?.specialization || "",
    experience_years: initialValues?.experience_years || "0",
    employment_type: initialValues?.employment_type || "fixed_salary",
    base_salary: initialValues?.base_salary || "0",
    default_commission_rate: initialValues?.default_commission_rate || "0",
    work_types: initialValues?.work_types || [],
    activity_ids: initialValues?.activity_ids || [],
    shift_ids: initialValues?.shift_ids || [],
  });

  const branchId1 = form.branch_ids?.[0];
  const branchId2 = form.branch_ids?.[1];
  const branchId3 = form.branch_ids?.[2];

  const { data: shiftsResponse1, isFetching: isLoadingShifts1 } =
    useGetBranchShiftsQuery(branchId1, { skip: !branchId1 });
  const { data: shiftsResponse2, isFetching: isLoadingShifts2 } =
    useGetBranchShiftsQuery(branchId2, { skip: !branchId2 });
  const { data: shiftsResponse3, isFetching: isLoadingShifts3 } =
    useGetBranchShiftsQuery(branchId3, { skip: !branchId3 });

  const isLoadingShifts =
    isLoadingShifts1 || isLoadingShifts2 || isLoadingShifts3;

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
        default_commission_rate:
          branchSettings.default_coach_commission_percentage
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
        typeof act.name === "object"
          ? act.name?.ar || act.name?.en || ""
          : act.name || "";
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
        const types = value || [];
        if (types.includes("equipment") && types.includes("activities")) {
          updated.employment_type = "hybrid";
        } else if (types.includes("equipment")) {
          updated.employment_type = "fixed_salary";
        } else if (types.includes("activities")) {
          updated.employment_type = "commission_based";
        } else {
          updated.employment_type = "fixed_salary";
        }
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
    const ageNum = Number(form.age);
    let dob = null;
    if (ageNum > 0) {
      const birthYear = new Date().getFullYear() - ageNum;
      dob = `${birthYear}-01-01`;
    }

    const shiftsPayload = hasEquipmentActivity
      ? (form.shift_ids || []).map(Number)
      : [];

    onSubmit({
      full_name: form.full_name.trim(),
      gender: form.gender,
      dob: dob,
      phone: form.phone.trim() || null,
      country_code: form.country_code.trim() || "+963",
      email: null,
      address: form.address.trim() || null,
      branch_ids: form.branch_ids,
      specialization: form.specialization.trim() || null,
      experience_years: Number(form.experience_years) || 0,
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
      <label className="block text-right text-sm text-app-muted-light">
        الاسم الكامل للمدرب *
        <input
          value={form.full_name}
          onChange={(event) => updateField("full_name", event.target.value)}
          aria-invalid={Boolean(errors && errors.full_name)}
          className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
            errors && errors.full_name
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          placeholder="الاسم الثلاثي للمدرب"
          required
        />
        {errors && errors.full_name && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.full_name}
          </span>
        )}
      </label>

      <div className="grid grid-cols-2 gap-3">
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
          العمر (بالسنوات) *
          <input
            value={form.age}
            onChange={(event) => updateField("age", event.target.value)}
            aria-invalid={Boolean(errors && errors.age)}
            className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
              errors && errors.age
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            placeholder="مثال: 30"
            type="number"
            min="15"
            max="100"
            required
          />
          {errors && errors.age && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.age}
            </span>
          )}
        </label>
      </div>

      <div>
        <PhoneField
          label="رقم الهاتف"
          phoneValue={form.phone}
          onPhoneChange={(val) => updateField("phone", val)}
          codeValue={form.country_code}
          onCodeChange={(val) => updateField("country_code", val)}
          required={false}
          className="text-right w-full"
          error={errors && (errors.phone || errors.country_code)}
        />
      </div>

      <div className="block text-right text-sm text-app-muted-light">
        الفروع التابع لها *
        <div
          className={`mt-2 grid grid-cols-2 gap-2 p-3 bg-app-card-soft rounded-lg max-h-36 overflow-y-auto ${
            errors && errors.branch_ids
              ? "border border-app-red"
              : "border border-app-line"
          }`}
        >
          {branches.map((b) => {
            const checked = form.branch_ids.includes(Number(b.id));
            const bName =
              typeof b.name === "object" ? b.name?.ar || b.name?.en : b.name;
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
                label:
                  typeof act.name === "object"
                    ? act.name?.ar || act.name?.en
                    : act.name,
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
                  typeof act.name === "object"
                    ? act.name?.ar || act.name?.en
                    : act.name;
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
                const isChecked = form.shift_ids?.some(
                  (id) => Number(id) === Number(shift.id),
                );
                const shiftNameStr = shift.name || "وردية بدون اسم";
                const startTime = shift.start_time
                  ? shift.start_time.slice(0, 5)
                  : "";
                const endTime = shift.end_time
                  ? shift.end_time.slice(0, 5)
                  : "";
                const gender =
                  shiftGenderLabels[shift.gender_allowed] ||
                  shift.gender_allowed ||
                  "مختلط";
                const label = `${shiftNameStr} | من ${startTime} إلى ${endTime} (${gender})`;

                return (
                  <Checkbox
                    key={shift.id}
                    label={label}
                    checked={isChecked}
                    onChange={() => {
                      const idNum = Number(shift.id);
                      const newShifts = isChecked
                        ? (form.shift_ids || []).filter(
                            (x) => Number(x) !== idNum,
                          )
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

      <label className="block text-right text-sm text-app-muted-light">
        التخصص التدريبي
        <input
          value={form.specialization}
          onChange={(event) =>
            updateField("specialization", event.target.value)
          }
          aria-invalid={Boolean(errors && errors.specialization)}
          className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
            errors && errors.specialization
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          placeholder="مثال: Yoga, CrossFit, Bodybuilding"
        />
        {errors && errors.specialization && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.specialization}
          </span>
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        سنوات الخبرة
        <input
          value={form.experience_years}
          onChange={(event) =>
            updateField("experience_years", event.target.value)
          }
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

      {form.employment_type !== "commission_based" &&
        form.employment_type !== "commission" && (
          <label className="block text-right text-sm text-app-muted-light">
            الراتب الأساسي ({CURRENCY_SYMBOL}) *
            <input
              value={form.base_salary}
              onChange={(event) =>
                updateField("base_salary", event.target.value)
              }
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
            onChange={(event) =>
              updateField("default_commission_rate", event.target.value)
            }
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
        <Button
          type="button"
          tone="outline"
          className="h-11 flex-1"
          onClick={onCancel}
        >
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          {initialValues ? "حفظ التعديل" : "إضافة المدرب"}
        </Button>
      </div>
    </form>
  );
}

export default function CoachesClient() {
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    employmentFilter,
    setEmploymentFilter,
    activityFilter,
    setActivityFilter,
    drawerMode,
    setDrawerMode,
    setSelectedCoachId,
    selectedCoachId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredCoaches,
    stats,
    selectedCoach,
    detailsCoach,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    isAddingActivity,
    isDeletingActivity,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    handleAddActivity,
    handleRemoveActivity,
    selectedActivityId,
    setSelectedActivityId,
    getEditInitialValues,
    branches,
    activities,
    closeDrawer,
  } = useCoaches();

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "المدرب",
        align: "start",
        render: (_, coach) => {
          let photoUrl = coach.person?.photo_url || coach.person?.photo;
          if (
            photoUrl &&
            !photoUrl.startsWith("http") &&
            !photoUrl.startsWith("blob:")
          ) {
            photoUrl = `http://31.70.108.63/${photoUrl.replace(/^\//, "")}`;
          }

          return (
            <div className="flex items-center gap-3 justify-start px-4">
              {photoUrl ? (
                <div
                  className="size-8 rounded-full bg-cover bg-center border border-app-line/30 shrink-0"
                  style={{ backgroundImage: `url(${photoUrl})` }}
                />
              ) : (
                <div className="size-8 rounded-full bg-app-card-soft flex items-center justify-center shrink-0 border border-app-line/30">
                  <span className="text-[11px] font-bold text-app-yellow">
                    {coach.person?.full_name?.charAt(0) || "-"}
                  </span>
                </div>
              )}
              <span className="text-sm font-medium text-white text-right">
                {coach.person?.full_name || "-"}
              </span>
            </div>
          );
        },
      },
      // {
      //   key: "specialization",
      //   label: "التخصص",
      //   align: "center",
      //   render: (_, coach) => (
      //     <span className="text-xs text-app-muted-light">
      //       {coach.details?.specialization || "غير محدد"}
      //     </span>
      //   ),
      // },
      {
        key: "employment_type",
        label: "التوظيف",
        align: "center",
        render: (value) => (
          <span className="text-xs text-app-muted-light">
            {employmentLabels[value] || value}
          </span>
        ),
      },
      {
        key: "base_salary",
        label: "الراتب / النسبة",
        align: "center",
        render: (_, coach) => {
          const type = coach.employment_type;
          const salary = formatMoney(coach.base_salary);
          const commission = Number(
            coach.details?.default_commission_rate || 0,
          );

          if (type === "commission_based" || type === "commission") {
            return (
              <span className="text-xs font-semibold text-app-green" dir="ltr">
                {commission}%
              </span>
            );
          } else if (type === "hybrid") {
            return (
              <div className="flex items-center justify-center gap-1.5 text-xs font-semibold text-app-green">
                <span>{salary}</span>
                <span className="text-app-muted-light">+</span>
                <span dir="ltr">{commission}%</span>
              </div>
            );
          }

          return (
            <span className="text-xs font-semibold text-app-green">
              {salary}
            </span>
          );
        },
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (value) => (
          <span
            className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${
              value
                ? "bg-app-green/10 text-app-green"
                : "bg-app-red/10 text-app-red"
            }`}
          >
            {value ? "نشط" : "غير نشط"}
          </span>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, coach) => (
          <RowActions
            disabled={isDeleting}
            editHref={`/management/coaches/create?mode=edit&id=${coach.id}`}
            onDelete={() => handleDelete(coach)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, setFormError, setSelectedCoachId, setDrawerMode],
  );

  const editInitialValues = useMemo(() => {
    return getEditInitialValues();
  }, [selectedCoach]);

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({ value: String(b.id), label: b.name })),
    ],
    [branches],
  );

  const employmentOptions = useMemo(
    () => [{ value: "all", label: "كل أنواع التوظيف" }, ...employmentTypes],
    [],
  );

  const activityOptions = useMemo(
    () => [
      { value: "all", label: "كل الأنشطة" },
      ...activities.map((a) => ({ value: String(a.id), label: a.name })),
    ],
    [activities],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة المدربين"
        subtitle="عرض قائمة الكوادر التدريبية، إحصاءات الأجور، تعيين المهام والرياضات لكل كوتش."
        action={
          <Button
            href="/management/coaches/create"
            icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
            style={{ color: "#000000" }}
          >
            إضافة مدرب
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة المدربين والكفاءات"
        columns={columns}
        rows={filteredCoaches}
        minWidth="800px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        isLoading={isLoading}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل بيانات المدربين.</p>
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا يوجد مدربون مسجلون حالياً."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(coach) => {
          setSelectedCoachId(coach.id);
          setDrawerMode("details");
        }}
        getRowKey={(coach) => coach.id}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
            <label className="relative block w-full sm:w-80 md:w-96">
              <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="البحث باسم المدرب أو التخصص..."
                type="search"
              />
            </label>

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={branchFilter}
              options={branchOptions}
              onChange={setBranchFilter}
            />

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={activityFilter}
              options={activityOptions}
              onChange={setActivityFilter}
            />

            <Dropdown
              className="min-w-48 bg-app-card-soft border-app-line text-white"
              icon={FilterIcon}
              value={employmentFilter}
              options={employmentOptions}
              onChange={setEmploymentFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredCoaches.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل المدرب"
        subtitle={
          detailsCoach?.person?.full_name ||
          selectedCoach?.person?.full_name ||
          ""
        }
      >
        <CoachDetails
          coach={detailsCoach || selectedCoach}
          branches={branches}
          allActivities={activities}
          isLoading={isFetchingDetails}
          error={detailsError}
          selectedActivityId={selectedActivityId}
          setSelectedActivityId={setSelectedActivityId}
          onAddActivity={handleAddActivity}
          onRemoveActivity={handleRemoveActivity}
          isAddingActivity={isAddingActivity}
          isRemovingActivity={isDeletingActivity}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف المدرب"
        message={`هل أنت متأكد من رغبتك في حذف المدرب "${itemToDelete ? itemToDelete.person?.full_name : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

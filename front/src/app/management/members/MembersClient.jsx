"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import Dropdown from "@/components/ui/Dropdown";
import { PlusIcon, SearchIcon, FilterIcon } from "@/components/icons/Icons";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { memberSchema } from "@/lib/validations/membersSchema";
import PhoneField from "@/components/forms/PhoneField";
import { useMembers } from "./useMembers";

const TABLE_GRID_COLUMNS =
  "minmax(180px,1.2fr) 140px 100px 120px 140px 100px 90px";
const CURRENCY_SYMBOL = "ل.س";

const genderLabels = {
  male: "ذكر",
  female: "أنثى",
};

const relationOptions = [
  { value: "Father", label: "الأب" },
  { value: "Mother", label: "الأم" },
  { value: "Brother", label: "الأخ" },
  { value: "Sister", label: "الأخت" },
  { value: "Friend", label: "صديق" },
  { value: "Other", label: "قريب / آخر" },
];

const relationLabels = {
  Father: "الأب",
  Mother: "الأم",
  Brother: "الأخ",
  Sister: "الأخت",
  Friend: "صديق",
  Other: "قريب / آخر",
};

export const memberInitialForm = {
  first_name: "",
  last_name: "",
  mobile_country_code: "+963",
  mobile: "",
  gender: "male",
  dob: "",
  age: "",
  branch_id: "",
  emergency_name: "",
  emergency_relation: "Father",
  emergency_country_code: "+963",
  emergency_phone: "",
  plan_id: "",
  paid_amount: "",
};

const initialForm = memberInitialForm;

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

function MemberDetails({ member, branches = [] }) {
  if (!member) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا العضو.
      </div>
    );
  }

  const person = member.person || {};
  const branchName =
    branches.find((b) => b.id === member.branch_id)?.name ||
    `فرع #${member.branch_id}`;

  const personalContact = person.contacts?.find(
    (c) => c.relation === "self" || c.name === "Personal",
  );
  const emergencyContact = person.contacts?.find((c) => c.relation !== "self");

  const fullName =
    person.full_name ||
    `${member.first_name || ""} ${member.last_name || ""}`.trim() ||
    "-";
  const gender = person.gender || member.gender || "-";
  const mobile = personalContact?.phone_number || person.phone || person.mobile || member.mobile || "";
  const countryCode =
    personalContact?.country_code || person.mobile_country_code || member.mobile_country_code || "";
  const dob = person.dob || member.dob || "";
  const age = person.age || member.age || "";

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-white">{fullName}</h3>
        <p className="mt-1 text-xs text-app-muted-light">
          رقم العضوية: #{member.id}
        </p>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="الفرع" value={branchName} tone="yellow" />
        <DetailItem label="الجنس" value={genderLabels[gender] || gender} />
        <DetailItem
          label="الهاتف"
          value={mobile ? `${countryCode} ${mobile}`.trim() : "-"}
        />
        <DetailItem label="تاريخ الميلاد" value={formatDate(dob)} />
        <DetailItem label="العمر" value={age ? `${age} سنة` : "-"} />
        <DetailItem
          label="حالة الاشتراك"
          value={member.is_active !== false ? "نشط" : "غير نشط"}
          tone={member.is_active !== false ? "green" : "red"}
        />
        <DetailItem
          label="تاريخ التسجيل"
          value={formatDate(member.created_at)}
        />
      </section>

      {/* Emergency Contact */}
      {emergencyContact && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right space-y-3">
          <h4 className="text-sm font-semibold text-white">
            جهة اتصال إضافية للطوارئ
          </h4>
          <section className="grid gap-3 sm:grid-cols-2">
            <DetailItem label="اسم القريب" value={emergencyContact.name} />
            <DetailItem
              label="العلاقة"
              value={relationLabels[emergencyContact.relation] || emergencyContact.relation}
            />
            <DetailItem
              label="رقم الهاتف"
              value={
                emergencyContact.phone_number
                  ? `${emergencyContact.country_code || ""} ${emergencyContact.phone_number}`
                  : "-"
              }
            />

          </section>
        </div>
      )}
    </div>
  );
}

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
}) {
  const [form, setForm] = useState(initialValues);
  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field])
      setErrors((current) => ({ ...current, [field]: null }));
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

    const enteredAge = form.age !== "" && form.age !== null && form.age !== undefined ? Number(form.age) : undefined;
    let calculatedDob = form.dob || null;
    if (enteredAge && !calculatedDob) {
      calculatedDob = `${new Date().getFullYear() - enteredAge}-01-01`;
    }

    const validationData = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      mobile_country_code: form.mobile_country_code.trim(),
      mobile: form.mobile.trim(),
      gender: form.gender,
      dob: calculatedDob,
      age: enteredAge,
      branch_id: Number(form.branch_id),
      emergency_name: form.emergency_name.trim(),
      emergency_relation: form.emergency_relation,
      emergency_phone: form.emergency_phone.trim(),
      emergency_country_code: form.emergency_country_code.trim(),
      plan_id: form.plan_id ? Number(form.plan_id) : undefined,
      paid_amount: form.paid_amount ? Number(form.paid_amount) : undefined,
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
      let customErrors = {};
      if (!form.plan_id) {
        customErrors.plan_id = "يرجى اختيار خطة الاشتراك";
      }
      if (form.plan_id && (!form.paid_amount && form.paid_amount !== 0 && form.paid_amount !== "0")) {
        customErrors.paid_amount = "يرجى ادخال المبلغ المدفوع";
      }
      if (Object.keys(customErrors).length > 0) {
        setErrors(customErrors);
        return;
      }

      const plansPayload = form.plan_id
        ? [
            {
              plan_id: Number(form.plan_id),
              paid_amount: Number(form.paid_amount) || 0,
            },
          ]
        : [];

      onSubmit({
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        mobile_country_code: form.mobile_country_code.trim(),
        mobile: form.mobile.trim(),
        gender: form.gender,
        dob: calculatedDob,
        age: enteredAge,
        branch_id: Number(form.branch_id),
        additional_contacts,
        plans: plansPayload,
      });
    } else {
      onSubmit({
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        mobile_country_code: form.mobile_country_code.trim(),
        mobile: form.mobile.trim(),
        gender: form.gender,
        dob: calculatedDob,
        age: enteredAge,
        branch_id: Number(form.branch_id),
        additional_contacts,
      });
    }
  }

  const selectedPlanObj = plans.find(
    (p) => String(p.id) === String(form.plan_id),
  );

  return (
    <form
      id={formId}
      noValidate
      onSubmit={handleSubmit}
      className={formClassName}
      dir="rtl"
    >
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

        <label className="block text-right text-sm text-app-muted-light">
          العمر *
          <input
            value={form.age}
            onChange={(event) => updateField("age", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            type="number"
            min="4"
            required
            placeholder="مثال: 25"
          />
          {errors && errors.age && (
            <span className="text-app-red text-xs mt-1 block text-right w-full">
              {errors.age}
            </span>
          )}
        </label>
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
            label: b.name,
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
            onChange={(event) =>
              updateField("emergency_name", event.target.value)
            }
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
            error={
              errors &&
              (errors.emergency_phone || errors.emergency_country_code)
            }
          />
        </div>
      </div>

      {/* Plan Section (Only in Create Mode) */}
      {mode === "create" && (
        <div className="border-t border-app-line pt-4 mt-2">
          <h4 className="text-sm font-semibold text-white mb-3">
            تسجيل في خطة اشتراك مباشرة
          </h4>

          <label className="block text-right text-sm text-app-muted-light mb-3">
            الخطة الرياضية
            <Dropdown
              className="mt-2 text-white"
              buttonClassName="bg-app-card-soft h-11"
              value={form.plan_id}
              onChange={(val) => {
                updateField("plan_id", val);
                const selected = plans.find(
                  (p) => String(p.id) === String(val),
                );
                if (selected) {
                  updateField(
                    "paid_amount",
                    String(selected.base_price || "0"),
                  );
                }
              }}
              options={plans.map((p) => ({
                value: String(p.id),
                label: p.name?.ar || p.name?.en || "",
              }))}
              placeholder="اختر خطة الاشتراك"
              error={errors && errors.plan_id}
            />
          </label>

          {form.plan_id && (
            <label className="block text-right text-sm text-app-muted-light">
              المبلغ المدفوع ({CURRENCY_SYMBOL})
              <input
                value={form.paid_amount}
                onChange={(event) =>
                  updateField("paid_amount", event.target.value)
                }
                className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
                type="number"
                min="0"
                placeholder={
                  selectedPlanObj
                    ? `القيمة الأساسية: ${selectedPlanObj.base_price}`
                    : ""
                }
              />
              {errors && errors.paid_amount && (
                <span className="text-app-red text-xs mt-1 block text-right w-full">
                  {errors.paid_amount}
                </span>
              )}
            </label>
          )}
        </div>
      )}

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div
        className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}
      >
        <Button
          type="button"
          tone="outline"
          className="h-11 flex-1"
          onClick={onCancel}
        >
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1" loading={isLoading}>
          {mode === "edit" ? "حفظ التعديل" : "إضافة العضو"}
        </Button>
      </div>
    </form>
  );
}

export default function MembersClient() {
  const {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    genderFilter,
    setGenderFilter,
    drawerMode,
    setDrawerMode,
    selectedMemberId,
    setSelectedMemberId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredMembers,
    stats,
    selectedMember,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    deleteConfirmOpen,
    itemToDelete,
    getEditInitialValues,
    branches,
    plans,
    closeDrawer,
  } = useMembers();

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "الاسم",
        align: "center",
        render: (_, member) => {
          const person = member.person || {};
          const fullName =
            person.full_name ||
            `${member.first_name || ""} ${member.last_name || ""}`.trim();
          return (
            <span className="text-sm font-medium text-white">
              {fullName || "-"}
            </span>
          );
        },
      },
      {
        key: "mobile",
        label: "رقم الهاتف",
        align: "center",
        render: (_, member) => {
          const person = member.person || {};
          const personalContact = person.contacts?.find(
            (c) => c.relation === "self" || c.name === "Personal",
          );
          const mobile = personalContact?.phone_number || person.phone || person.mobile || member.mobile;
          const countryCode =
            personalContact?.country_code || person.mobile_country_code || member.mobile_country_code || "";
          return (
            <span className="text-xs text-app-muted-light" dir="ltr">
              {mobile ? `${countryCode} ${mobile}`.trim() : "-"}
            </span>
          );
        },
      },
      {
        key: "gender",
        label: "الجنس",
        align: "center",
        render: (_, member) => {
          const person = member.person || {};
          const gender = person.gender || member.gender;
          return (
            <span className="text-xs text-app-muted-light">
              {genderLabels[gender] || gender || "-"}
            </span>
          );
        },
      },
      {
        key: "dob",
        label: "تاريخ الميلاد",
        align: "center",
        render: (_, member) => {
          const person = member.person || {};
          const dob = person.dob || member.dob;
          return (
            <span className="text-xs text-app-muted-light">
              {formatDate(dob)}
            </span>
          );
        },
      },
      {
        key: "branch_id",
        label: "الفرع",
        align: "center",
        render: (value) => {
          const branchName =
            branches.find((b) => b.id === value)?.name || `فرع #${value}`;
          return (
            <span className="text-xs text-app-muted-light">{branchName}</span>
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
              value !== false
                ? "bg-app-green/10 text-app-green"
                : "bg-app-red/10 text-app-red"
            }`}
          >
            {value !== false ? "نشط" : "غير نشط"}
          </span>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, member) => (
          <RowActions
            disabled={isDeleting}
            editHref={`/management/members/create?mode=edit&id=${member.id}`}
            onDelete={() => handleDelete(member)}
          />
        ),
      },
    ],
    [branches, isDeleting, handleDelete],
  );

  const editInitialValues = useMemo(() => {
    return getEditInitialValues();
  }, [selectedMember, branches]);

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({ value: String(b.id), label: b.name })),
    ],
    [branches],
  );

  const genderOptions = [
    { value: "all", label: "كل الفئات" },
    { value: "male", label: "ذكور" },
    { value: "female", label: "إناث" },
  ];

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة الأعضاء واللاعبين"
        subtitle="تسجيل الأعضاء، وتحديث البيانات الشخصية، وربط جهات اتصال الطوارئ وتفاصيل العضوية."
        action={
          <Button
            href="/management/members/create"
            icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
            style={{ color: "#000000" }}
          >
            إضافة لاعب
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة اللاعبين"
        columns={columns}
        rows={filteredMembers}
        minWidth="850px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        isLoading={isLoading}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل بيانات الأعضاء.</p>
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا يوجد أعضاء لاعبون مسجلون حالياً."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(member) => {
          setSelectedMemberId(member.id);
          setDrawerMode("details");
        }}
        getRowKey={(member) => member.id}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label className="relative block min-w-64">
              <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="البحث باسم اللاعب أو الهاتف..."
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
              value={genderFilter}
              options={genderOptions}
              onChange={setGenderFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredMembers.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل بيانات اللاعب"
        subtitle={
          selectedMember
            ? `${selectedMember.first_name} ${selectedMember.last_name}`
            : ""
        }
      >
        {editInitialValues && (
          <MemberForm
            key={selectedMemberId || "edit"}
            mode="edit"
            branches={branches}
            initialValues={editInitialValues}
            onSubmit={handleUpdate}
            onCancel={closeDrawer}
            isLoading={isUpdating}
            errorMessage={formError}
          />
        )}
      </Drawer>

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل اللاعب العضو"
        subtitle={
          selectedMember
            ? `${selectedMember.first_name} ${selectedMember.last_name}`
            : ""
        }
      >
        <MemberDetails member={selectedMember} branches={branches} />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف العضو"
        message={`هل أنت متأكد من رغبتك في حذف العضو "${itemToDelete ? `${itemToDelete.first_name} ${itemToDelete.last_name}` : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

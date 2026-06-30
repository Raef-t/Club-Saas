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
import { useMembers } from "./useMembers";

const TABLE_GRID_COLUMNS = "minmax(180px,1.2fr) 140px 100px 120px 140px 100px 90px";
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

const initialForm = {
  first_name: "",
  last_name: "",
  mobile_country_code: "+963",
  mobile: "",
  gender: "male",
  dob: "",
  branch_id: "",
  emergency_name: "",
  emergency_relation: "Father",
  emergency_country_code: "+963",
  emergency_phone: "",
  plan_id: "",
  paid_amount: "",
};

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
    branches.find((b) => b.id === member.branch_id)?.name || `فرع #${member.branch_id}`;

  const contact = member.additional_contacts?.[0] || null;

  const fullName = person.full_name || `${member.first_name || ""} ${member.last_name || ""}`.trim() || "-";
  const gender = person.gender || member.gender || "-";
  const mobile = person.phone || person.mobile || member.mobile || "";
  const countryCode = person.mobile_country_code || member.mobile_country_code || "";
  const dob = person.dob || member.dob || "";
  const age = person.age || member.age || "";

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <h3 className="text-lg font-medium text-white">
          {fullName}
        </h3>
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
        <DetailItem label="تاريخ التسجيل" value={formatDate(member.created_at)} />
      </section>

      {/* Emergency Contact */}
      {contact && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/50 p-4 text-right space-y-3">
          <h4 className="text-sm font-semibold text-white">جهة اتصال إضافية للطوارئ</h4>
          <section className="grid gap-3 sm:grid-cols-2">
            <DetailItem label="اسم القريب" value={contact.name} />
            <DetailItem label="العلاقة" value={relationLabels[contact.relation] || contact.relation} />
            <DetailItem
              label="رقم الهاتف"
              value={contact.phone_number ? `${contact.country_code || ""} ${contact.phone_number}` : "-"}
            />
          </section>
        </div>
      )}
    </div>
  );
}

function MemberForm({
  mode,
  initialValues = initialForm,
  branches = [],
  plans = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState(initialValues);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
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

    const calculatedAge = form.dob
      ? new Date().getFullYear() - new Date(form.dob).getFullYear()
      : 25;

    if (mode === "create") {
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
        dob: form.dob || null,
        age: calculatedAge,
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
        dob: form.dob || null,
        branch_id: Number(form.branch_id),
        additional_contacts,
      });
    }
  }

  const selectedPlanObj = plans.find((p) => String(p.id) === String(form.plan_id));

  return (
    <form onSubmit={handleSubmit} className="space-y-4" dir="rtl">
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
        </label>
      </div>

      <div className="grid grid-cols-3 gap-3">
        <label className="col-span-2 block text-right text-sm text-app-muted-light">
          رقم الهاتف المحمول
          <input
            value={form.mobile}
            onChange={(event) => updateField("mobile", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="0501234567"
            dir="ltr"
            required
          />
        </label>
        <label className="block text-right text-sm text-app-muted-light">
          رمز الدولة
          <input
            value={form.mobile_country_code}
            onChange={(event) => updateField("mobile_country_code", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-center outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="+963"
            dir="ltr"
            required
          />
        </label>
      </div>

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
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          تاريخ الميلاد
          <input
            value={form.dob}
            onChange={(event) => updateField("dob", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            type="date"
          />
        </label>
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        الفرع
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.branch_id}
          onChange={(val) => updateField("branch_id", val)}
          options={branches.map((b) => ({ value: String(b.id), label: b.name }))}
          placeholder="اختر الفرع"
        />
      </label>

      {/* Emergency Contact */}
      <div className="border-t border-app-line pt-4 mt-2">
        <h4 className="text-sm font-semibold text-white mb-3">جهة اتصال إضافية للطوارئ (اختياري)</h4>

        <label className="block text-right text-sm text-app-muted-light mb-3">
          الاسم
          <input
            value={form.emergency_name}
            onChange={(event) => updateField("emergency_name", event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
            placeholder="مثال: والد اللاعب"
          />
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
            />
          </label>

          <div className="grid grid-cols-3 gap-2">
            <label className="col-span-2 block text-right text-sm text-app-muted-light">
              رقم الهاتف
              <input
                value={form.emergency_phone}
                onChange={(event) => updateField("emergency_phone", event.target.value)}
                className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
                placeholder="0509876543"
                dir="ltr"
              />
            </label>
            <label className="block text-right text-sm text-app-muted-light">
              الرمز
              <input
                value={form.emergency_country_code}
                onChange={(event) => updateField("emergency_country_code", event.target.value)}
                className="app-input mt-2 h-11 w-full px-3 text-center outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
                placeholder="+963"
                dir="ltr"
              />
            </label>
          </div>
        </div>
      </div>

      {/* Plan Section (Only in Create Mode) */}
      {mode === "create" && (
        <div className="border-t border-app-line pt-4 mt-2">
          <h4 className="text-sm font-semibold text-white mb-3">تسجيل في خطة اشتراك مباشرة (اختياري)</h4>

          <label className="block text-right text-sm text-app-muted-light mb-3">
            الخطة الرياضية
            <Dropdown
              className="mt-2 text-white"
              buttonClassName="bg-app-card-soft h-11"
              value={form.plan_id}
              onChange={(val) => {
                updateField("plan_id", val);
                const selected = plans.find((p) => String(p.id) === String(val));
                if (selected) {
                  updateField("paid_amount", String(selected.base_price || "0"));
                }
              }}
              options={plans.map((p) => ({
                value: String(p.id),
                label: p.name?.ar || p.name?.en || "",
              }))}
              placeholder="اختر خطة الاشتراك"
            />
          </label>

          {form.plan_id && (
            <label className="block text-right text-sm text-app-muted-light">
              المبلغ المدفوع ({CURRENCY_SYMBOL})
              <input
                value={form.paid_amount}
                onChange={(event) => updateField("paid_amount", event.target.value)}
                className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
                type="number"
                min="0"
                placeholder={selectedPlanObj ? `القيمة الأساسية: ${selectedPlanObj.base_price}` : ""}
              />
            </label>
          )}
        </div>
      )}

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
          const fullName = person.full_name || `${member.first_name || ""} ${member.last_name || ""}`.trim();
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
          const mobile = person.phone || person.mobile || member.mobile;
          const countryCode = person.mobile_country_code || member.mobile_country_code || "";
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
            <span className="text-xs text-app-muted-light">{formatDate(dob)}</span>
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
          return <span className="text-xs text-app-muted-light">{branchName}</span>;
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
            onEdit={() => {
              setFormError("");
              setSelectedMemberId(member.id);
              setDrawerMode("edit");
            }}
            onDelete={() => handleDelete(member)}
          />
        ),
      },
    ],
    [branches, setSelectedMemberId, setDrawerMode, setFormError, isDeleting, handleDelete],
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
            icon={<PlusIcon className="size-4" />}
            onClick={() => {
              setFormError("");
              setDrawerMode("create");
            }}
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
        open={drawerMode === "create"}
        onClose={closeDrawer}
        title="إضافة لاعب جديد"
        subtitle="أدخل بيانات اللاعب ورقم الهاتف والفرع التابع له"
      >
        <MemberForm
          mode="create"
          branches={branches}
          plans={plans}
          onSubmit={handleCreate}
          onCancel={closeDrawer}
          isLoading={isCreating}
          errorMessage={formError}
          initialValues={{
            ...initialForm,
            branch_id: branches[0]?.id ? String(branches[0].id) : "",
          }}
        />
      </Drawer>

      <Drawer
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل بيانات اللاعب"
        subtitle={selectedMember ? `${selectedMember.first_name} ${selectedMember.last_name}` : ""}
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
        subtitle={selectedMember ? `${selectedMember.first_name} ${selectedMember.last_name}` : ""}
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

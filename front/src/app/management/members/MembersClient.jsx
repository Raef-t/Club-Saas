"use client";

import { MemberForm } from "./MemberForm";

import { useMemo } from "react";
import { useRouter } from "next/navigation";
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
import { formatLocalizedName } from "@/lib/utils";
import { getMemberDisplayName } from "./memberDisplayName";
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
      <p className={`mt-1 truncate text-sm font-medium ${toneClass}`}>{value ?? "-"}</p>
    </div>
  );
}

export default function MembersClient({ initialData }) {
  const router = useRouter();
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
    deleteConfirmation,
    setDeleteConfirmation,
    getEditInitialValues,
    branches,
    plans,
    closeDrawer,
  } = useMembers({ initialData });

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "الاسم",
        align: "center",
        render: (_, member) => (
          <span className="text-sm font-medium text-white">
            {getMemberDisplayName(member) || "-"}
          </span>
        ),
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
          const mobile =
            personalContact?.phone_number || person.phone || person.mobile || member.mobile;
          const countryCode =
            personalContact?.country_code ||
            person.mobile_country_code ||
            member.mobile_country_code ||
            "";
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
          return <span className="text-xs text-app-muted-light">{formatDate(dob)}</span>;
        },
      },
      {
        key: "branch_id",
        label: "الفرع",
        align: "center",
        render: (value) => {
          const branchName =
            formatLocalizedName(branches.find((b) => b.id === value)?.name) || `فرع #${value}`;
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
              value !== false ? "bg-app-green/10 text-app-green" : "bg-app-red/10 text-app-red"
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
      ...branches.map((b) => ({ value: String(b.id), label: formatLocalizedName(b.name) })),
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
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetch}>
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
          router.push(`/management/members/${member.id}`);
        }}
        getRowKey={(member) => member.id}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
            <label className="relative block w-full sm:w-80 md:w-96">
              <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-white outline-none transition focus:border-app-yellow/70"
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
        subtitle={getMemberDisplayName(selectedMember)}
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

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف العضو"
        message={`هل أنت متأكد من رغبتك في حذف العضو "${getMemberDisplayName(itemToDelete)}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={deleteConfirmation}
        onConfirmationChange={setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={isDeleting}
      />
    </div>
  );
}

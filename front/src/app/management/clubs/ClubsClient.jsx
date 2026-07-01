"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import { PlusIcon } from "@/components/icons/Icons";
import { CheckboxField } from "@/components/forms/CheckboxField";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DetailItem from "@/components/ui/DetailItem";
import SearchInput from "@/components/ui/SearchInput";
import { Field } from "@/components/forms/FormControls";
import { useClubs } from "./useClubs";
import { formatDate } from "@/lib/utils";
import { clubSchema } from "@/lib/validations/clubsSchema";

const TABLE_GRID_COLUMNS = "80px minmax(180px,1.5fr) 140px 140px 120px";

const initialForm = {
  name: "",
  logo_url: "",
  is_active: true,
};

function StatusBadge({ active }) {
  return (
    <span
      className={`inline-flex min-w-20 justify-center rounded-md px-3 py-1 text-xs font-medium ${
        active ? "status-success" : "status-danger"
      }`}
    >
      {active ? "نشط" : "غير نشط"}
    </span>
  );
}

function ClubLogo({ src, name, className = "size-10" }) {
  const [error, setError] = useState(false);

  if (!src || error) {
    const initial = name ? name.charAt(0).toUpperCase() : "?";
    return (
      <div
        className={`flex items-center justify-center rounded-lg bg-app-card-hover border border-app-line font-bold text-app-yellow ${className}`}
      >
        {initial}
      </div>
    );
  }

  return (
    <img
      src={src}
      alt={name}
      onError={() => setError(true)}
      className={`rounded-lg object-cover border border-app-line bg-app-card-hover ${className}`}
    />
  );
}


function ClubDetails({ club, isLoading, error }) {
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
        تعذر تحميل تفاصيل النادي.
      </div>
    );
  }

  if (!club) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا النادي.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <div className="flex items-center justify-between gap-4">
          <div className="min-w-0">
            <h3 className="text-lg font-medium text-app-text">
              {club.name || "-"}
            </h3>
            <p className="mt-1 text-xs text-app-muted-light">
              معرف النادي: #{club.id}
            </p>
          </div>
          <ClubLogo src={club.logo_url} name={club.name} className="size-16" />
        </div>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem
          label="حالة النشاط"
          value={club.is_active ? "نشط" : "غير نشط"}
          tone={club.is_active ? "green" : "red"}
        />
        <DetailItem label="تاريخ الإنشاء" value={formatDate(club.created_at)} />
        <DetailItem label="تاريخ التحديث" value={formatDate(club.updated_at)} />
      </section>
    </div>
  );
}

function ClubForm({
  mode,
  initialValues = initialForm,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState(initialValues);
  const [errors, setErrors] = useState({});

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const data = {
      name: form.name.trim(),
      logo_url: form.logo_url?.trim() || null,
      is_active: Boolean(form.is_active),
    };
    
    const result = clubSchema.safeParse(data);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach(issue => {
        formattedErrors[issue.path.join('_')] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }
    
    setErrors({});
    onSubmit(data);
  }

  return (
    <form noValidate onSubmit={handleSubmit} className="space-y-4">
      <Field
        label="اسم النادي"
        value={form.name}
        onChange={(event) => updateField("name", event.target.value)}
        placeholder="تكنوجيم"
        required
        type="text"
       error={errors.name}
        />

      {/* 
      <label className="block text-right text-sm text-app-muted-light">
        رابط الشعار (اختياري)
        <input
          value={form.logo_url || ""}
          onChange={(event) => updateField("logo_url", event.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-left outline-none focus:border-app-yellow/70"
          placeholder="https://example.com/logo.png"
          dir="ltr"
        />
      </label> */}

      <CheckboxField
        label="تفعيل النادي"
        checked={form.is_active}
        onChange={(event) => updateField("is_active", event.target.checked)}
      />

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
          {mode === "edit" ? "حفظ التعديل" : "إنشاء النادي"}
        </Button>
      </div>
    </form>
  );
}

export default function ClubsClient() {
  const {
    search,
    setSearch,
    drawerMode,
    setDrawerMode,
    setSelectedClubId,
    selectedClubId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredClubs,
    stats,
    selectedClub,
    detailsClub,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    closeDrawer,
    getEditInitialValues,
    deleteConfirmOpen,
    itemToDelete,
    closeDeleteConfirm,
    confirmDelete,
  } = useClubs();

  const columns = useMemo(
    () => [
      {
        key: "logo_url",
        label: "الشعار",
        align: "center",
        render: (value, club) => (
          <div className="flex justify-center">
            <ClubLogo src={value} name={club.name} className="size-10" />
          </div>
        ),
      },
      {
        key: "name",
        label: "الاسم",
        align: "center",
        render: (value) => (
          <span className="text-sm font-medium text-app-text">
            {value || "-"}
          </span>
        ),
      },
      {
        key: "created_at",
        label: "تاريخ الإنشاء",
        align: "center",
        render: (value) => formatDate(value),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (value) => <StatusBadge active={value} />,
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, club) => (
          <RowActions
            disabled={isDeleting}
            onEdit={() => {
              setFormError("");
              setSelectedClubId(club.id);
              setDrawerMode("edit");
            }}
            onDelete={() => handleDelete(club)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, setFormError, setSelectedClubId, setDrawerMode],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النظام"
        title="إدارة النوادي"
        subtitle="عرض النوادي المسجلة في النظام، وتعديل بياناتها أو إضافة نوادٍ جديدة."
        action={
          <Button
            icon={<PlusIcon className="size-4" />}
            onClick={() => {
              setFormError("");
              setDrawerMode("create");
            }}
          >
            إضافة نادٍ
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة النوادي"
        columns={columns}
        rows={filteredClubs}
        minWidth="750px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        isLoading={isLoading}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل بيانات النوادي.</p>
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد نوادٍ مسجلة حالياً."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(club) => {
          setSelectedClubId(club.id);
          setDrawerMode("details");
        }}
        getRowKey={(club) => club.id}
        toolbarActions={
          <SearchInput
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="البحث باسم النادي..."
            className="min-w-72"
          />
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredClubs.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "create"}
        onClose={closeDrawer}
        title="إضافة نادٍ جديد"
        subtitle="أدخل اسم النادي ورابط الشعار"
      >
        <ClubForm
          mode="create"
          onSubmit={handleCreate}
          onCancel={closeDrawer}
          isLoading={isCreating}
          errorMessage={formError}
        />
      </Drawer>

      <Drawer
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل بيانات النادي"
        subtitle={selectedClub?.name}
      >
        <ClubForm
          key={selectedClubId || "edit"}
          mode="edit"
          initialValues={getEditInitialValues()}
          onSubmit={handleUpdate}
          onCancel={closeDrawer}
          isLoading={isUpdating}
          errorMessage={formError}
        />
      </Drawer>

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل النادي"
        subtitle={detailsClub?.name || selectedClub?.name}
      >
        <ClubDetails
          club={detailsClub || selectedClub}
          isLoading={isFetchingDetails}
          error={detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف النادي"
        message={`هل أنت متأكد من رغبتك في حذف نادي "${itemToDelete?.name}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

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
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import DetailItem from "@/components/ui/DetailItem";
import SearchInput from "@/components/ui/SearchInput";
import { Field } from "@/components/forms/FormControls";
import { useSubscriptionPlans } from "./useSubscriptionPlans";
import { formatLocalizedName } from "@/lib/utils";
import { subscriptionPlanSchema } from "@/lib/validations/subscriptionPlansSchema";

const TABLE_GRID_COLUMNS =
  "minmax(180px,1.25fr) 78px 82px 94px 90px 112px 86px 88px";

const initialForm = {
  name: "",
  duration_in_days: "30",
  price: "",
};

function formatMoney(value) {
  // Use our centralized formatMoney with '$' symbol
  const { formatMoney: baseFormat } = require("@/lib/utils");
  return baseFormat(value, "$");
}

const planName = formatLocalizedName;

function planTypeLabel(type) {
  const labels = {
    monthly: "شهري",
    weekly: "أسبوعي",
    yearly: "سنوي",
    custom: "مخصص",
  };

  return labels[type] || type || "-";
}

function StatusBadge({ active }) {
  return (
    <span
      className={`inline-flex min-w-20 justify-center rounded-md px-3 py-1 text-xs font-medium ${
        active ? "status-success" : "status-danger"
      }`}
    >
      {active ? "فعالة" : "متوقفة"}
    </span>
  );
}


function PlanDetails({ plan, isLoading, error }) {
  if (isLoading) {
    return <SkeletonPage blocks={[{ type: "details", sections: 2, itemsPerSection: 4 }]} />;
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل الخطة.
      </div>
    );
  }

  if (!plan) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذه الخطة.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h3 className="truncate text-lg font-medium text-app-text">
              {planName(plan)}
            </h3>
            <p className="mt-1 text-xs text-app-muted-light">
              {plan.name?.en || "Subscription plan"}
            </p>
          </div>
          <StatusBadge active={plan.is_active} />
        </div>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="نوع الخطة" value={planTypeLabel(plan.type)} />
        <DetailItem label="السعر" value={formatMoney(plan.base_price)} tone="yellow" />
        <DetailItem label="المدة" value={`${plan.duration_days || 0} يوم`} />
        <DetailItem label="عدد الجلسات" value={plan.session_count} />
        <DetailItem label="عدد مرات التجميد" value={plan.max_freeze_count} />
        <DetailItem label="أيام التجميد" value={plan.max_freeze_days} />
      </section>
    </div>
  );
}

function PlanForm({
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
      duration_in_days: Number(form.duration_in_days),
      price: Number(form.price),
    };
    
    const result = subscriptionPlanSchema.safeParse(data);
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
        label="اسم الخطة"
        value={form.name}
        onChange={(event) => updateField("name", event.target.value)}
        placeholder="الاشتراك الفضي"
        required
        type="text"
       error={errors.name}
        />

      <Field
        label="المدة بالأيام"
        value={form.duration_in_days}
        onChange={(event) => updateField("duration_in_days", event.target.value)}
        type="number"
        min="1"
        required
       error={errors.duration_in_days}
        />

      <Field
        label="السعر"
        value={form.price}
        onChange={(event) => updateField("price", event.target.value)}
        type="number"
        min="0"
        step="0.01"
        placeholder="350"
        required
       error={errors.price}
        />

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
          {mode === "edit" ? "حفظ التعديل" : "إنشاء الخطة"}
        </Button>
      </div>
    </form>
  );
}

export default function SubscriptionPlansClient() {
  const {
    search,
    setSearch,
    drawerMode,
    setDrawerMode,
    selectedPlanId,
    setSelectedPlanId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredPlans,
    stats,
    selectedPlan,
    detailsPlan,
    isFetchingDetails,
    isLoadingDetails,
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
  } = useSubscriptionPlans();

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "الخطة",
        align: "center",
        render: (_, plan) => (
          <div className="min-w-0 text-center">
            <p className="truncate text-sm font-medium text-app-text">
              {planName(plan)}
            </p>
            <p className="mt-1 truncate text-[11px] text-app-muted-light">
              {plan.name?.en || "-"}
            </p>
          </div>
        ),
      },
      {
        key: "type",
        label: "النوع",
        align: "center",
        render: (value) => planTypeLabel(value),
      },
      {
        key: "duration_days",
        label: "المدة",
        align: "center",
        render: (value) => `${value || 0} يوم`,
      },
      {
        key: "session_count",
        label: "الجلسات",
        align: "center",
        render: (value) => `${value || 0} جلسة`,
      },
      {
        key: "base_price",
        label: "السعر",
        align: "center",
        render: (value) => (
          <span className="font-medium text-app-yellow">{formatMoney(value)}</span>
        ),
      },
      {
        key: "freeze",
        label: "التجميد",
        align: "center",
        render: (_, plan) => `${plan.max_freeze_count || 0} مرة / ${plan.max_freeze_days || 0} يوم`,
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
        render: (_, plan) => (
          <RowActions
            disabled={isDeleting}
            onEdit={() => {
              setFormError("");
              setSelectedPlanId(plan.id);
              setDrawerMode("edit");
            }}
            onDelete={() => handleDelete(plan)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete, setFormError, setSelectedPlanId, setDrawerMode],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="خطط الاشتراك"
        subtitle="إنشاء وتعديل خطط الاشتراك وربطها مع مدة الخطة والسعر وعدد الجلسات."
        action={
          <Button
            icon={<PlusIcon className="size-4" />}
            onClick={() => {
              setFormError("");
              setDrawerMode("create");
            }}
          >
            إنشاء خطة
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة خطط الاشتراك"
        columns={columns}
        rows={filteredPlans}
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
              <p className="text-app-red">تعذر تحميل خطط الاشتراك.</p>
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetch}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد خطط مطابقة للبحث الحالي."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        totalPages={0}
        onRowClick={(plan) => {
          setSelectedPlanId(plan.id);
          setDrawerMode("details");
        }}
        getRowKey={(plan) => plan.id}
        toolbarActions={
          <SearchInput
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="بحث باسم الخطة أو السعر"
            className="min-w-72"
          />
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredPlans.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={drawerMode === "create"}
        onClose={closeDrawer}
        title="إنشاء خطة اشتراك"
        subtitle="أدخل اسم الخطة والمدة والسعر"
      >
        <PlanForm
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
        title="تعديل خطة الاشتراك"
        subtitle={planName(selectedPlan)}
      >
        <PlanForm
          key={selectedPlanId || "edit"}
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
        title="تفاصيل خطة الاشتراك"
        subtitle={planName(detailsPlan || selectedPlan)}
      >
        <PlanDetails
          plan={detailsPlan || selectedPlan}
          isLoading={isLoadingDetails || isFetchingDetails}
          error={detailsError}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف خطة الاشتراك"
        message={`هل أنت متأكد من رغبتك في حذف خطة "${itemToDelete ? planName(itemToDelete) : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

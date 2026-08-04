"use client";

import { useMemo, useState, useEffect } from "react";
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
import { CheckboxField } from "@/components/forms/CheckboxField";
import Dropdown from "@/components/ui/Dropdown";
import { useSubscriptionPlans } from "./useSubscriptionPlans";
import { formatLocalizedName, formatMoney as baseFormatMoney } from "@/lib/utils";
import { subscriptionPlanSchema } from "@/lib/validations/subscriptionPlansSchema";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  getPreferredBranchId,
  getGenderForBranchId,
} from "@/lib/managementBranchUtils";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { addMinutesToTime } from "./subscriptionPlanTimeUtils";
import {
  getSubscriptionPlanStatusMeta,
  SUBSCRIPTION_PLAN_STATUS,
} from "./subscriptionPlanStatus";

function CoachDropdown({ branchId, activityId, value, onChange }) {
  const { data, isLoading } = useGetCoachesQuery(
    {
      branch_id: branchId || undefined,
      activity_id: activityId || undefined,
    },
    {
      skip: !branchId && !activityId,
    },
  );

  const coaches = useMemo(() => (Array.isArray(data?.data) ? data.data : []), [data]);

  return (
    <Dropdown
      className="mt-1 text-white"
      buttonClassName="bg-black/35 h-9"
      value={String(value || "")}
      onChange={onChange}
      options={coaches.map((c) => ({
        value: String(c.id),
        label: c.person?.full_name || String(c.id),
      }))}
      placeholder={isLoading ? "جاري التحميل..." : "اختر المدرب"}
    />
  );
}

const TABLE_GRID_COLUMNS = "minmax(180px,1.25fr) 78px 82px 94px 90px 112px 86px 88px";

const initialForm = {
  branch_id: "",
  name: "",
  sessions_per_week: "",
  session_count: "",
  price: "",
  max_subscribers: "50",
  is_active: true,
  status: SUBSCRIPTION_PLAN_STATUS.ACTIVE,
  gender_restriction: "mixed",
  activities: [],
  session_templates: [],
  is_unlimited_subscribers: false,
};

function formatMoney(value) {
  return baseFormatMoney(value, "$");
}

const planName = (plan) => formatLocalizedName(plan?.name);



function StatusBadge({ plan }) {
  const status = getSubscriptionPlanStatusMeta(plan);

  return (
    <span
      className={`inline-flex min-w-20 justify-center rounded-md px-3 py-1 text-xs font-medium ${status.className}`}
    >
      {status.label}
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
        تعذر تحميل تفاصيل الفعالية.
      </div>
    );
  }

  if (!plan) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذه الفعالية.
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h3 className="truncate text-lg font-medium text-app-text">{planName(plan)}</h3>
            <p className="mt-1 text-xs text-app-muted-light">
              {plan.name?.en || "Subscription plan"}
            </p>
          </div>
          <StatusBadge plan={plan} />
        </div>
      </div>

      <section className="grid gap-3 sm:grid-cols-2">
        <DetailItem label="السعر" value={formatMoney(plan.base_price)} tone="yellow" />
        <DetailItem label="الجلسات أسبوعياً" value={plan.sessions_per_week || "-"} />
        <DetailItem label="عدد الجلسات الإجمالي" value={plan.session_count || "-"} />
      </section>
    </div>
  );
}

export function PlanForm({
  mode,
  initialValues = initialForm,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
  branches = [],
  activities = [],
  coaches = [],
}) {
  const { selectedBranchId } = useManagementBranch();
  const [form, setForm] = useState(() => ({
    ...initialValues,
    branch_id: getPreferredBranchId({
      currentBranchId: initialValues.branch_id,
      selectedBranchId,
      branches,
    }),
  }));
  const [errors, setErrors] = useState({});
  useEffect(() => {
    setForm((current) => ({
      ...current,
      gender_restriction: getGenderForBranchId(
        branches,
        current.branch_id,
        current.gender_restriction,
      ),
    }));
  }, [branches, form.branch_id]);

  useEffect(() => {
    let shouldBeUnlimited = false;
    form.activities?.forEach((item) => {
      const act = activities.find((a) => String(a.id) === String(item.activity_id));
      if (act) {
        const actName =
          typeof act.name === "string" ? act.name : act.name?.ar || act.name?.en || "";
        if (actName.includes("تدريب عام") || actName.includes("تدريب خاص")) {
          shouldBeUnlimited = true;
        }
      }
    });

    if (shouldBeUnlimited && !form.is_unlimited_subscribers) {
      updateField("is_unlimited_subscribers", true);
    }
  }, [form.activities, activities]);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    const rawData = {
      branch_id: form.branch_id,
      name: form.name?.trim(),
      gender_restriction: form.gender_restriction,
      sessions_per_week: form.sessions_per_week,
      session_count: form.session_count,
      price: form.price,
      max_subscribers: form.is_unlimited_subscribers ? null : form.max_subscribers,
      is_active: !!form.is_active,
      status:
        form.status === SUBSCRIPTION_PLAN_STATUS.COMPLETED
          ? SUBSCRIPTION_PLAN_STATUS.COMPLETED
          : form.is_active
            ? SUBSCRIPTION_PLAN_STATUS.ACTIVE
            : SUBSCRIPTION_PLAN_STATUS.INACTIVE,
      is_unlimited_subscribers: !!form.is_unlimited_subscribers,
      activities:
        form.activities?.map((a) => ({
          activity_id: Number(a.activity_id),
          coach_id: Number(a.coach_id),
        })) || [],
      session_templates:
        form.session_templates?.map((s) => ({
          day_of_week: Number(s.day_of_week),
          start_time: s.start_time,
          end_time: s.end_time,
        })) || [],
    };

    const result = subscriptionPlanSchema.safeParse(rawData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(result.data);
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName}>
      <label className="block text-right text-sm text-app-muted-light">
        الفرع *
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
          error={errors.branch_id}
        />
      </label>

      <Field
        label="اسم الفعالية"
        value={form.name}
        onChange={(event) => updateField("name", event.target.value)}
        placeholder="الاشتراك الفضي"
        required
        type="text"
        error={errors.name}
      />



      <label className="block text-right text-sm text-app-muted-light">
        تخصيص الجنس *
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.gender_restriction}
          onChange={(val) => updateField("gender_restriction", val)}
          options={[
            { value: "mixed", label: "مختلط" },
            { value: "male", label: "ذكور" },
            { value: "female", label: "إناث" },
          ]}
          placeholder="اختر تخصيص الجنس"
          error={errors.gender_restriction}
        />
      </label>

      <Field
        label="عدد الجلسات في الأسبوع"
        value={form.sessions_per_week}
        onChange={(event) => updateField("sessions_per_week", event.target.value)}
        type="number"
        min="1"
        error={errors.sessions_per_week}
      />

      <Field
        label="عدد الجلسات (الإجمالي)"
        value={form.session_count}
        onChange={(event) => updateField("session_count", event.target.value)}
        type="number"
        min="1"
        placeholder="مثال: 12"
        error={errors.session_count}
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

      <div className="border-t border-app-line pt-4 mt-2">
        <div className="flex items-center justify-between mb-3">
          <h4 className="text-sm font-semibold text-white">الأنشطة والمدربين</h4>
          <Button
            type="button"
            tone="outline"
            className="h-8 px-3 text-xs"
            onClick={() =>
              updateField("activities", [
                ...(form.activities || []),
                { activity_id: "", coach_id: "" },
              ])
            }
          >
            <PlusIcon className="me-1 size-3" />
            إضافة نشاط
          </Button>
        </div>

        {!form.activities || form.activities.length === 0 ? (
          <p className="text-xs text-app-muted-light">لم يتم إضافة أي نشاط بعد.</p>
        ) : (
          <div className="space-y-3">
            {form.activities.map((item, idx) => (
              <div
                key={idx}
                className="flex flex-col gap-2 p-3 bg-app-card-soft rounded-lg border border-app-line relative"
              >
                <button
                  type="button"
                  onClick={() => {
                    const newActs = [...form.activities];
                    newActs.splice(idx, 1);
                    updateField("activities", newActs);
                  }}
                  className="absolute end-3 top-2 text-xs text-app-muted hover:text-app-red"
                >
                  إزالة
                </button>
                <div className="grid grid-cols-2 gap-3 mt-4">
                  <label className="block text-right text-xs text-app-muted-light">
                    النشاط الرياضي
                    <Dropdown
                      className="mt-1 text-white"
                      buttonClassName="bg-black/35 h-9"
                      value={String(item.activity_id)}
                      onChange={(val) => {
                        const newActs = [...form.activities];
                        newActs[idx].activity_id = val;
                        newActs[idx].coach_id = "";
                        updateField("activities", newActs);
                      }}
                      options={activities.map((a) => ({
                        value: String(a.id),
                        label: typeof a.name === "string" ? a.name : a.name?.ar || a.name?.en || "",
                      }))}
                      placeholder="اختر النشاط"
                    />
                  </label>
                  <label className="block text-right text-xs text-app-muted-light">
                    المدرب
                    <CoachDropdown
                      branchId={form.branch_id}
                      activityId={item.activity_id}
                      value={item.coach_id}
                      onChange={(val) => {
                        const newActs = [...form.activities];
                        newActs[idx].coach_id = val;
                        updateField("activities", newActs);
                      }}
                    />
                  </label>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {(() => {
        const shouldShowMaxSubscribers = form.activities?.some((item) => {
          const act = activities.find((a) => String(a.id) === String(item.activity_id));
          return act?.activity_type?.is_session_based === true;
        });

        if (!shouldShowMaxSubscribers) return null;

        return (
          <>
            <CheckboxField
              label="مفتوح المشتركين (غير محدود)"
              checked={form.is_unlimited_subscribers}
              onChange={(e) => updateField("is_unlimited_subscribers", e.target.checked)}
            />

            <Field
              label="الحد الأقصى للمشتركين"
              value={form.is_unlimited_subscribers ? "غير محدود" : form.max_subscribers}
              onChange={(event) => updateField("max_subscribers", event.target.value)}
              type={form.is_unlimited_subscribers ? "text" : "number"}
              min="1"
              disabled={form.is_unlimited_subscribers}
              required={!form.is_unlimited_subscribers}
              error={!form.is_unlimited_subscribers ? errors.max_subscribers : null}
            />
          </>
        );
      })()}

      <div className="border-t border-app-line pt-4 mt-2">
        <div className="flex items-center justify-between mb-3">
          <h4 className="text-sm font-semibold text-white">جدول أوقات الفعالية</h4>
          <Button
            type="button"
            tone="outline"
            className="h-8 px-3 text-xs"
            onClick={() => {
              let nextDay = "0";
              let defaultStartTime = "";
              let defaultEndTime = "";

              if (form.session_templates && form.session_templates.length > 0) {
                const lastTemplate = form.session_templates[form.session_templates.length - 1];

                const hasAutoSequenceActivity =
                  form.activities?.length > 0 &&
                  form.activities.some((item) => {
                    const act = activities.find((a) => String(a.id) === String(item.activity_id));
                    if (act) {
                      const actName =
                        typeof act.name === "string"
                          ? act.name
                          : act.name?.ar || act.name?.en || "";
                      const isGeneralOrPrivate =
                        actName.includes("أجهزة عام") ||
                        actName.includes("أجهزة خاص") ||
                        actName.includes("تدريب عام") ||
                        actName.includes("تدريب خاص");
                      return !isGeneralOrPrivate;
                    }
                    return false;
                  });

                if (hasAutoSequenceActivity) {
                  nextDay = String((parseInt(lastTemplate.day_of_week) + 2) % 7);
                  defaultStartTime = lastTemplate.start_time || "";
                  defaultEndTime = lastTemplate.end_time || "";
                }
              }

              updateField("session_templates", [
                ...(form.session_templates || []),
                { day_of_week: nextDay, start_time: defaultStartTime, end_time: defaultEndTime },
              ]);
            }}
          >
            <PlusIcon className="me-1 size-3" />
            إضافة وقت
          </Button>
        </div>

        {!form.session_templates || form.session_templates.length === 0 ? (
          <p className="text-xs text-app-muted-light">لم يتم إضافة أي أوقات للفعالية بعد.</p>
        ) : (
          <div className="space-y-3">
            {form.session_templates.map((item, idx) => (
              <div
                key={idx}
                className="flex flex-col gap-2 p-3 bg-app-card-soft rounded-lg border border-app-line relative"
              >
                <button
                  type="button"
                  onClick={() => {
                    const newTemplates = [...form.session_templates];
                    newTemplates.splice(idx, 1);
                    updateField("session_templates", newTemplates);
                  }}
                  className="absolute end-3 top-2 text-xs text-app-muted hover:text-app-red"
                >
                  إزالة
                </button>
                <div className="grid grid-cols-2 gap-3 mt-4">
                  <label className="block text-right text-xs text-app-muted-light col-span-2">
                    اليوم
                    <Dropdown
                      className="mt-1 text-white"
                      buttonClassName="bg-black/35 h-9"
                      value={String(item.day_of_week)}
                      onChange={(val) => {
                        const newTemplates = [...form.session_templates];
                        newTemplates[idx].day_of_week = val;
                        updateField("session_templates", newTemplates);
                      }}
                      options={[
                        { value: "0", label: "الأحد" },
                        { value: "1", label: "الإثنين" },
                        { value: "2", label: "الثلاثاء" },
                        { value: "3", label: "الأربعاء" },
                        { value: "4", label: "الخميس" },
                        { value: "5", label: "الجمعة" },
                        { value: "6", label: "السبت" },
                      ]}
                      placeholder="اختر اليوم"
                    />
                  </label>
                  <Field
                    label="وقت البدء"
                    type="time"
                    value={item.start_time}
                    onChange={(val) => {
                      const newTemplates = [...form.session_templates];
                      const startTime = val && val.target ? val.target.value : val;
                      newTemplates[idx] = {
                        ...newTemplates[idx],
                        start_time: startTime,
                        end_time: addMinutesToTime(startTime),
                      };
                      updateField("session_templates", newTemplates);
                    }}
                    required
                    className="h-9"
                    labelClassName="text-xs text-app-muted-light"
                  />
                  <Field
                    label="وقت الانتهاء"
                    type="time"
                    value={item.end_time}
                    onChange={(val) => {
                      const newTemplates = [...form.session_templates];
                      newTemplates[idx].end_time = val && val.target ? val.target.value : val;
                      updateField("session_templates", newTemplates);
                    }}
                    required
                    className="h-9"
                    labelClassName="text-xs text-app-muted-light"
                  />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <CheckboxField
        label="الفعالية فعالة ونشطة حالياً"
        checked={form.is_active}
        onChange={(e) => {
          const isActive = e.target.checked;
          setForm((current) => ({
            ...current,
            is_active: isActive,
            status: isActive
              ? SUBSCRIPTION_PLAN_STATUS.ACTIVE
              : SUBSCRIPTION_PLAN_STATUS.INACTIVE,
          }));
        }}
      />

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}>
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button
          type="submit"
          className="h-11 flex-1"
          loading={isLoading}
          style={{ color: "#000000" }}
        >
          {mode === "edit" ? "حفظ التعديل" : "إنشاء الفعالية"}
        </Button>
      </div>
    </form>
  );
}

export default function SubscriptionPlansClient({ initialData }) {
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
    branches,
  } = useSubscriptionPlans({ initialData });

  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "الفعالية",
        align: "center",
        render: (_, plan) => (
          <div className="min-w-0 text-center">
            <p className="truncate text-sm font-medium text-app-text">{planName(plan)}</p>
            <p className="mt-1 truncate text-[11px] text-app-muted-light">{plan.name?.en || "-"}</p>
          </div>
        ),
      },
      {
        key: "sessions_per_week",
        label: "جلسات/أسبوع",
        align: "center",
        render: (value) => value || "-",
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
        key: "subscribers",
        label: "المشتركين",
        align: "center",
        render: (_, plan) =>
          plan.is_unlimited_subscribers
            ? `${plan.current_subscribers || 0} / غير محدود`
            : `${plan.current_subscribers || 0} / ${plan.max_subscribers || 0}`,
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        render: (_, plan) => <StatusBadge plan={plan} />,
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, plan) => (
          <RowActions
            disabled={isDeleting}
            editHref={`/management/subscription-plans/create?mode=edit&id=${plan.id}`}
            onDelete={() => handleDelete(plan)}
          />
        ),
      },
    ],
    [isDeleting, handleDelete],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="الفعاليات"
        subtitle="إنشاء وتعديل الفعاليات وربطها مع مدة الفعالية والسعر وعدد الجلسات."
        action={
          <Button
            href="/management/subscription-plans/create"
            icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
            style={{ color: "#000000" }}
          >
            إنشاء فعالية
          </Button>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة الفعاليات"
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
              <p className="text-app-red">تعذر تحميل الفعاليات.</p>
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetch}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد فعاليات مطابقة للبحث الحالي."
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
            placeholder="بحث باسم الفعالية أو السعر"
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
        open={drawerMode === "edit"}
        onClose={closeDrawer}
        title="تعديل الفعالية"
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
          branches={branches}
        />
      </Drawer>

      <Drawer
        open={drawerMode === "details"}
        onClose={closeDrawer}
        title="تفاصيل الفعالية"
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
        title="تأكيد حذف الفعالية"
        message={`هل أنت متأكد من رغبتك في حذف فعالية "${itemToDelete ? planName(itemToDelete) : ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

"use client";

import { useMemo, useState } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import Drawer from "@/components/ui/Drawer";
import SkeletonPage from "@/components/ui/Skeleton";
import StatsGrid from "@/components/ui/StatsGrid";
import {
  FilterIcon,
  SearchIcon,
  PlusIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import { useSubscriptions } from "./useSubscriptions";
import { useToast } from "@/components/ui/Toast";
import { subscriptionSchema } from "@/lib/validations/subscriptionsSchema";
const statusLabels = {
  active: "نشط",
  expired: "منتهي",
  cancelled: "ملغي",
  frozen: "مجمّد",
  pending: "قيد الانتظار",
};

const statusClasses = {
  active: "status-success",
  expired: "status-danger",
  cancelled: "status-danger",
  frozen: "status-warning",
  pending: "status-review",
};

const statusOptions = [
  { value: "all", label: "كل الحالات" },
  { value: "active", label: "نشط" },
  { value: "expired", label: "منتهي" },
  { value: "frozen", label: "مجمّد" },
  { value: "cancelled", label: "ملغي" },
  { value: "pending", label: "قيد الانتظار" },
];

const CURRENCY_SYMBOL = "$";
const TABLE_GRID_COLUMNS =
  "minmax(180px,1.25fr) minmax(180px,1.1fr) 88px 128px 88px 88px 88px";

function parseAmount(value) {
  const number = Number.parseFloat(value || 0);
  return Number.isFinite(number) ? number : 0;
}

function formatMoney(value) {
  return `${CURRENCY_SYMBOL}${parseAmount(value).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })}`;
}

function formatDate(value) {
  if (!value) return "-";

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "-";

  return new Intl.DateTimeFormat("ar", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(date);
}

function StatusBadge({ status }) {
  return (
    <span
      className={`inline-flex min-w-20 justify-center rounded-md px-3 py-1 text-xs font-medium ${
        statusClasses[status] || "bg-white/10 text-app-muted-light"
      }`}
    >
      {statusLabels[status] || status || "-"}
    </span>
  );
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
        {value || "-"}
      </p>
    </div>
  );
}

function DetailSection({ title, children }) {
  return (
    <section className="space-y-3">
      <h3 className="text-sm font-medium text-app-yellow">{title}</h3>
      <div className="grid gap-3 sm:grid-cols-2">{children}</div>
    </section>
  );
}

function SubscriptionDetails({
  subscription,
  error,
  isLoading,
  onRetry,
  onFreeze,
  onUnfreeze,
  onCancel,
  isFreezing,
  isUnfreezing,
  isCancelling,
}) {
  const [showFreezeForm, setShowFreezeForm] = useState(false);
  const [daysCount, setDaysCount] = useState("7");
  const [startDate, setStartDate] = useState(
    new Date().toISOString().split("T")[0],
  );

  if (isLoading) {
    return (
      <SkeletonPage
        blocks={[{ type: "details", sections: 4, itemsPerSection: 4 }]}
      />
    );
  }

  if (error) {
    return (
      <div className="rounded-xl border border-app-red/30 bg-app-red/10 p-5 text-right text-sm text-app-red">
        تعذر تحميل تفاصيل الاشتراك المحدد.
      </div>
    );
  }

  if (!subscription) {
    return (
      <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-6 text-center text-sm text-app-muted-light">
        لا توجد تفاصيل لهذا الاشتراك.
      </div>
    );
  }

  const member = subscription.member || {};
  const person = member.person || {};
  const plan = subscription.plan || {};
  const planName = plan.name?.ar || plan.name?.en || "-";

  return (
    <div className="space-y-6">
      <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h3 className="truncate text-lg font-medium text-app-text">
              {person.full_name || "-"}
            </h3>
            <p className="mt-1 text-xs text-app-muted-light" dir="ltr">
              {member.member_number || "-"}
            </p>
          </div>
          <StatusBadge status={subscription.status} />
        </div>
      </div>

      <DetailSection title="بيانات العضو">
        <DetailItem label="الاسم" value={person.full_name} />
        <DetailItem label="رقم العضوية" value={member.member_number} />
        <DetailItem label="البريد الإلكتروني" value={person.email} />
        <DetailItem label="الهاتف" value={person.phone} />
      </DetailSection>

      <DetailSection title="الخطة والمدة">
        <DetailItem label="الخطة" value={planName} />
        <DetailItem label="نوع الخطة" value={plan.type} />
        <DetailItem
          label="تاريخ البداية"
          value={formatDate(subscription.start_date)}
        />
        <DetailItem
          label="تاريخ النهاية"
          value={formatDate(subscription.end_date)}
        />
        <DetailItem label="عدد الجلسات" value={plan.session_count} />
        <DetailItem
          label="الجلسات المتبقية"
          value={subscription.remaining_sessions}
          tone="yellow"
        />
      </DetailSection>

      <DetailSection title="المدفوعات">
        <DetailItem
          label="إجمالي الاشتراك"
          value={formatMoney(subscription.total_amount)}
          tone="yellow"
        />
        <DetailItem
          label="المدفوع"
          value={formatMoney(subscription.paid_amount)}
          tone="green"
        />
        <DetailItem
          label="المتبقي"
          value={formatMoney(subscription.remaining_amount)}
          tone={
            parseAmount(subscription.remaining_amount) > 0 ? "red" : "green"
          }
        />
        <DetailItem
          label="المدرب المسؤول"
          value={subscription.coach?.person?.full_name || "-"}
        />
      </DetailSection>

      {/* Freeze Logs */}
      {subscription.freezes && subscription.freezes.length > 0 && (
        <div className="rounded-xl border border-app-line bg-app-card-soft/40 p-4 text-right space-y-2">
          <h4 className="text-xs font-semibold text-white">
            سجل تجميد الاشتراك
          </h4>
          <div className="space-y-2">
            {subscription.freezes.map((f, i) => (
              <div
                key={i}
                className="flex items-center justify-between p-2 rounded bg-black/25 text-xs text-app-text border border-app-line"
              >
                <span className="text-app-muted-light">
                  {formatDate(f.start_date)} ← {formatDate(f.end_date)}
                </span>
                <span className="font-semibold text-app-yellow">
                  {f.days_count} يوم تجميد
                </span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Action Buttons Section */}
      <div className="border-t border-app-line pt-4 space-y-3">
        {subscription.status === "active" && (
          <>
            {!showFreezeForm ? (
              <div className="flex gap-3">
                <Button
                  type="button"
                  tone="warning"
                  className="flex-1 h-10 text-sm font-semibold"
                  onClick={() => setShowFreezeForm(true)}
                >
                  تجميد الاشتراك
                </Button>
                <Button
                  type="button"
                  tone="danger"
                  className="flex-1 h-10 text-sm font-semibold"
                  onClick={() => onCancel(subscription.id)}
                  loading={isCancelling}
                >
                  إلغاء الاشتراك
                </Button>
              </div>
            ) : (
              <div className="rounded-xl border border-app-line bg-app-card-soft p-4 space-y-3 text-right">
                <h4 className="text-xs font-bold text-white">
                  تجميد الاشتراك الحالي
                </h4>
                <div className="grid grid-cols-2 gap-3">
                  <DatePickerSmart
                    label="تاريخ البدء"
                    value={startDate}
                    onChange={setStartDate}
                    compact={true}
                  />
                  <label className="block text-xs text-app-muted-light">
                    عدد الأيام
                    <input
                      type="number"
                      min="1"
                      value={daysCount}
                      onChange={(e) => setDaysCount(e.target.value)}
                      className="app-input mt-1.5 h-9 w-full px-2 text-right bg-black/35 text-white"
                    />
                  </label>
                </div>
                <div className="flex gap-2 pt-1">
                  <Button
                    type="button"
                    tone="outline"
                    className="h-9 px-3 text-xs flex-1"
                    onClick={() => setShowFreezeForm(false)}
                  >
                    تراجع
                  </Button>
                  <Button
                    type="button"
                    tone="warning"
                    className="h-9 px-3 text-xs flex-1"
                    loading={isFreezing}
                    onClick={() => {
                      onFreeze(subscription.id, {
                        start_date: startDate,
                        days_count: Number(daysCount) || 7,
                      });
                      setShowFreezeForm(false);
                    }}
                  >
                    تأكيد التجميد
                  </Button>
                </div>
              </div>
            )}
          </>
        )}

        {subscription.status === "frozen" && (
          <Button
            type="button"
            className="w-full h-10 text-sm font-semibold"
            loading={isUnfreezing}
            onClick={() => onUnfreeze(subscription.id)}
          >
            إلغاء التجميد وتفعيل الاشتراك
          </Button>
        )}
      </div>
    </div>
  );
}

export function SubscriptionCreateForm({
  members = [],
  plans = [],
  activities = [],
  coaches = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
}) {
  const toast = useToast();
  const [form, setForm] = useState({
    member_id: members[0]?.id ? String(members[0].id) : "",
    plan_id: plans[0]?.id ? String(plans[0].id) : "",
    paid_amount: plans[0]?.base_price ? String(plans[0].base_price) : "0",
    start_date: "",
  });
  const [errors, setErrors] = useState({});

  const [selectedActivities, setSelectedActivities] = useState([]);
  const [currActivityId, setCurrActivityId] = useState("");
  const [currCoachId, setCurrCoachId] = useState("");

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field])
      setErrors((current) => ({ ...current, [field]: null }));
  }

  function handleAddActivity() {
    if (!currActivityId || !currCoachId) return;

    // Avoid duplicates
    if (
      selectedActivities.some(
        (item) => String(item.activity_id) === String(currActivityId),
      )
    ) {
      toast.warning("تمت إضافة هذا النشاط مسبقاً.");
      return;
    }

    const activityObj = activities.find(
      (a) => String(a.id) === String(currActivityId),
    );
    const coachObj = coaches.find((c) => String(c.id) === String(currCoachId));

    setSelectedActivities((prev) => [
      ...prev,
      {
        activity_id: Number(currActivityId),
        coach_id: Number(currCoachId),
        activityName: activityObj
          ? typeof activityObj.name === "string"
            ? activityObj.name
            : activityObj.name?.ar || activityObj.name?.en
          : "",
        coachName: coachObj?.person?.full_name || `مدرب #${currCoachId}`,
      },
    ]);

    setCurrActivityId("");
    setCurrCoachId("");
  }

  function handleRemoveActivity(actId) {
    setSelectedActivities((prev) =>
      prev.filter((item) => item.activity_id !== actId),
    );
  }

  function handleSubmit(event) {
    event.preventDefault();

    const data = {
      member_id: Number(form.member_id),
      plan_id: Number(form.plan_id),
      paid_amount: Number(form.paid_amount) || 0,
      start_date: form.start_date || undefined,
      payment_method: "cash",
      activities: selectedActivities.map((act) => ({
        activity_id: act.activity_id,
        coach_id: act.coach_id,
      })),
    };

    const result = subscriptionSchema.safeParse(data);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(data);
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
      <label className="block text-right text-sm text-app-muted-light">
        اللاعب العضو
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.member_id}
          onChange={(val) => updateField("member_id", val)}
          options={members.map((m) => ({
            value: String(m.id),
            label: `${m.person?.full_name || `${m.first_name || ""} ${m.last_name || ""}`} (رقم العضوية: #${m.id})`,
          }))}
          placeholder="اختر اللاعب"
          error={errors && errors.member_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        خطة الاشتراك
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.plan_id}
          onChange={(val) => {
            updateField("plan_id", val);
            const planObj = plans.find((p) => String(p.id) === String(val));
            if (planObj) {
              updateField("paid_amount", String(planObj.base_price || "0"));
            }
          }}
          options={plans.map((p) => ({
            value: String(p.id),
            label: `${p.name?.ar || p.name?.en || ""} - ${formatMoney(p.base_price)}`,
          }))}
          placeholder="اختر الخطة"
          error={errors && errors.plan_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        المبلغ المدفوع للاشتراك ({CURRENCY_SYMBOL})
        <input
          type="number"
          min="0"
          value={form.paid_amount}
          onChange={(e) => updateField("paid_amount", e.target.value)}
          className="app-input mt-2 h-11 w-full px-3 text-right outline-none focus:border-app-yellow/70 bg-app-card-soft text-white"
          placeholder={
            selectedPlanObj
              ? `السعر الأساسي: ${selectedPlanObj.base_price}`
              : ""
          }
          required
        />
        {errors && errors.paid_amount && (
          <span className="text-app-red text-xs mt-1 block">
            {errors.paid_amount}
          </span>
        )}
      </label>

      <DatePickerSmart
        label="تاريخ بداية الاشتراك"
        value={form.start_date}
        onChange={(val) => updateField("start_date", val)}
        compact={false}
      />

      {/* Dynamic Activities Selection */}
      <div className="border-t border-app-line pt-4 mt-2 text-right">
        <h4 className="text-sm font-semibold text-white mb-3">
          الأنشطة والمدربين المنسوبين للاشتراك
        </h4>

        {selectedActivities.length > 0 && (
          <div className="space-y-2 mb-3">
            {selectedActivities.map((act) => (
              <div
                key={act.activity_id}
                className="flex items-center justify-between p-2 rounded bg-black/35 text-xs text-app-text border border-app-line"
              >
                <button
                  type="button"
                  onClick={() => handleRemoveActivity(act.activity_id)}
                  className="text-app-red p-1 rounded hover:bg-app-red/10 transition"
                >
                  <TrashIcon className="size-4" />
                </button>
                <span>
                  {act.activityName} · المدرب: {act.coachName}
                </span>
              </div>
            ))}
          </div>
        )}

        <div className="flex gap-2 items-end bg-app-card-soft/40 p-3 rounded-xl border border-app-line">
          <div className="flex-1 space-y-2">
            <label className="block text-xs text-app-muted-light">
              اختر النشاط
            </label>
            <Dropdown
              className="text-white bg-black/45 rounded text-xs"
              buttonClassName="h-9"
              value={currActivityId}
              onChange={setCurrActivityId}
              options={activities.map((a) => ({
                value: String(a.id),
                label:
                  typeof a.name === "string"
                    ? a.name
                    : a.name?.ar || a.name?.en || "",
              }))}
              placeholder="الرياضة / النشاط"
            />
          </div>

          <div className="flex-1 space-y-2">
            <label className="block text-xs text-app-muted-light">
              اختر المدرب
            </label>
            <Dropdown
              className="text-white bg-black/45 rounded text-xs"
              buttonClassName="h-9"
              value={currCoachId}
              onChange={setCurrCoachId}
              options={coaches.map((c) => ({
                value: String(c.id),
                label: c.person?.full_name || `مدرب #${c.id}`,
              }))}
              placeholder="المدرب الكوتش"
            />
          </div>

          <Button
            type="button"
            className="h-9 px-3 text-xs"
            disabled={!currActivityId || !currCoachId}
            onClick={handleAddActivity}
          >
            إضافة
          </Button>
        </div>
      </div>

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
          إنشاء الاشتراك
        </Button>
      </div>
    </form>
  );
}

export default function SubscriptionsClient() {
  const {
    search,
    setSearch,
    status,
    setStatus,
    branchFilter,
    setBranchFilter,
    selectedSubscriptionId,
    setSelectedSubscriptionId,
    drawerMode,
    setDrawerMode,
    formError,
    setFormError,
    error,
    isFetching,
    isLoading,
    refetch,
    subscriptionDetailError,
    isSubscriptionDetailFetching,
    isSubscriptionDetailLoading,
    refetchSubscriptionDetail,
    selectedSubscription,
    filteredSubscriptions,
    stats,
    errorMessage,
    branches,
    members,
    plans,
    activities,
    coaches,
    isCreating,
    isFreezing,
    isUnfreezing,
    isCancelling,
    handleCreateSubscription,
    handleFreeze,
    handleUnfreeze,
    handleCancel,
    closeDrawer,
  } = useSubscriptions();

  const subscriptionColumns = useMemo(
    () => [
      {
        key: "member",
        label: "العضو",
        align: "center",
        render: (_, subscription) => {
          const member = subscription.member || {};
          const person = member.person || {};

          return (
            <div className="min-w-0 text-center">
              <p className="truncate text-sm font-medium text-app-text">
                {person.full_name || "-"}
              </p>
              <p
                className="mt-1 truncate text-[11px] text-app-muted-light"
                dir="ltr"
              >
                {member.member_number || "-"} · {person.phone || "-"}
              </p>
            </div>
          );
        },
      },
      {
        key: "plan",
        label: "الخطة",
        align: "center",
        render: (_, subscription) => {
          const plan = subscription.plan || {};
          const planName = plan.name?.ar || plan.name?.en || "-";

          return (
            <div className="min-w-0 text-center">
              <p className="truncate font-medium text-app-text">{planName}</p>
              <p className="mt-1 text-[11px] text-app-muted-light">
                {plan.session_count ? `${plan.session_count} جلسة` : "مفتوح"}
              </p>
            </div>
          );
        },
      },
      // {
      //   key: "branch",
      //   label: "الفرع",
      //   align: "center",
      //   render: (_, subscription) => {
      //     const branchName =
      //       branches.find((b) => b.id === subscription.branch_id)?.name ||
      //       (subscription.branch_id ? `فرع #${subscription.branch_id}` : "-");
      //     return (
      //       <span className="text-xs text-app-muted-light">{branchName}</span>
      //     );
      //   },
      // },
      {
        key: "remaining_amount",
        label: "المتبقي",
        align: "center",
        render: (value) => (
          <span className="font-medium text-app-red">{formatMoney(value)}</span>
        ),
      },
      {
        key: "dates",
        label: "تاريخ الصلاحية",
        align: "center",
        render: (_, subscription) => (
          <div className="text-center text-[11px]">
            <p className="text-app-muted-light">
              {formatDate(subscription.start_date)}
            </p>
            <p className="mt-0.5 text-app-yellow">
              {formatDate(subscription.end_date)}
            </p>
          </div>
        ),
      },
      {
        key: "status",
        label: "الحالة",
        align: "center",
        render: (value) => <StatusBadge status={value} />,
      },
    ],
    [branches],
  );

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({ value: String(b.id), label: b.name })),
    ],
    [branches],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="اشتراكات الأعضاء"
        subtitle="متابعة خطط الأعضاء، حالة الاشتراك، التجميدات، وإلغاء وتعديل المدفوعات."
        action={
          <div className="flex gap-3">
            <Button
              tone="outline"
              className="h-10 px-4"
              onClick={refetch}
              disabled={isFetching}
            >
              {isFetching ? "جاري التحديث" : "تحديث البيانات"}
            </Button>
            <Button
              href="/management/subscriptions/create"
              icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
              style={{ color: "#000000" }}
            >
              تسجيل اشتراك
            </Button>
          </div>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة اشتراكات الأعضاء"
        columns={subscriptionColumns}
        rows={filteredSubscriptions}
        minWidth="930px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        isLoading={isLoading}
        loadingRows={5}
        emptyMessage={
          error ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">{errorMessage || "تعذر تحميل الاشتراكات."}</p>
              <Button
                tone="outline"
                className="h-9 px-3 text-xs"
                onClick={refetch}
              >
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد اشتراكات مطابقة للبحث الحالي."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        onRowClick={(subscription) =>
          setSelectedSubscriptionId(subscription.id)
        }
        getRowKey={(subscription) => subscription.id}
        totalPages={0}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label className="relative block min-w-64">
              <SearchIcon className="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full pr-9 pl-3 text-sm outline-none transition focus:border-app-yellow/70 bg-app-card-soft text-white"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="بحث باسم العضو أو رقم العضوية"
                type="search"
              />
            </label>

            <Dropdown
              className="min-w-48 bg-app-card-soft text-white"
              icon={FilterIcon}
              value={status}
              options={statusOptions}
              onChange={setStatus}
            />

            <Dropdown
              className="min-w-48 bg-app-card-soft text-white"
              icon={FilterIcon}
              value={branchFilter}
              options={branchOptions}
              onChange={setBranchFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredSubscriptions.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      <Drawer
        open={Boolean(selectedSubscriptionId)}
        onClose={closeDrawer}
        title="تفاصيل الاشتراك"
        subtitle={
          selectedSubscription?.member?.person?.full_name ||
          (selectedSubscriptionId
            ? `رقم الاشتراك ${selectedSubscriptionId}`
            : "")
        }
      >
        <SubscriptionDetails
          subscription={selectedSubscription}
          error={subscriptionDetailError}
          isLoading={
            isSubscriptionDetailLoading || isSubscriptionDetailFetching
          }
          onRetry={refetchSubscriptionDetail}
          onFreeze={handleFreeze}
          onUnfreeze={handleUnfreeze}
          onCancel={handleCancel}
          isFreezing={isFreezing}
          isUnfreezing={isUnfreezing}
          isCancelling={isCancelling}
        />
      </Drawer>
    </div>
  );
}

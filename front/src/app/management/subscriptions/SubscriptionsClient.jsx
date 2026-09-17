"use client";

import Link from "next/link";
import SubscriptionDetails from "./SubscriptionDetails";
import SubscriptionStatusBadge from "./SubscriptionStatusBadge";
import SubscriptionReceiptBadges from "./SubscriptionReceiptBadges";
import SubscriptionAmountBadges from "./SubscriptionAmountBadges";
import SubscriptionMemberIdentity from "./SubscriptionMemberIdentity";
import RenewSubscriptionModal from "./RenewSubscriptionModal";
import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import Drawer from "@/components/ui/Drawer";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import StatsGrid from "@/components/ui/StatsGrid";
import {
  FilterIcon,
  PencilIcon,
  PlusIcon,
  RefreshIcon,
  SearchIcon,
  TrashIcon,
} from "@/components/icons/Icons";
import { useSubscriptions } from "./useSubscriptions";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import { SUBSCRIPTION_PERIOD_OPTIONS, SUBSCRIPTION_STATUS_OPTIONS } from "./subscriptionConstants";
import { getSubscriptionCreatorName } from "./subscriptionUtils";
import { usePermissions } from "@/lib/PermissionContext";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";
import { getMemberAccountName } from "@/lib/memberIdentity";

const TABLE_GRID_COLUMNS =
  "44px minmax(0,1.45fr) minmax(0,1.1fr) minmax(0,.95fr) minmax(0,.8fr) minmax(0,1.2fr) minmax(0,.9fr) 96px 152px";

const ACTION_BUTTON_CLASS =
  "grid size-8 shrink-0 place-items-center rounded-lg border transition disabled:cursor-not-allowed disabled:opacity-50";

function MoreVerticalIcon({ className = "size-4" }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <circle cx="12" cy="5" r="1.5" />
      <circle cx="12" cy="12" r="1.5" />
      <circle cx="12" cy="19" r="1.5" />
    </svg>
  );
}

function SubscriptionTableActions({
  subscription,
  canView,
  canUpdate,
  canDelete,
  canRenew,
  isBusy,
  onView,
  onDelete,
  onRenew,
}) {
  const canRenewSubscription = canRenew && subscription.status === "finished";

  return (
    <div className="flex w-full items-center justify-center gap-1.5" dir="rtl">
      {canUpdate && (
        <Link
          href={`/management/subscriptions/create?mode=edit&id=${subscription.id}`}
          title="تعديل الاشتراك"
          aria-label="تعديل الاشتراك"
          onClick={(event) => {
            event.stopPropagation();
            if (isBusy) event.preventDefault();
          }}
          className={`${ACTION_BUTTON_CLASS} border-app-line bg-slate-500/15 text-app-muted-light hover:border-slate-400/50 hover:bg-slate-500/25 hover:text-app-text ${
            isBusy ? "pointer-events-none opacity-50" : ""
          }`}
          aria-disabled={isBusy}
        >
          <PencilIcon className="size-4" />
        </Link>
      )}

      {canRenew &&
        (canRenewSubscription ? (
          <button
            type="button"
            title="تجديد الاشتراك"
            aria-label="تجديد الاشتراك"
            className={`${ACTION_BUTTON_CLASS} border-app-yellow/35 bg-app-yellow/15 text-app-yellow hover:border-app-yellow/70 hover:bg-app-yellow/25`}
            onClick={(event) => {
              event.stopPropagation();
              onRenew(subscription);
            }}
            disabled={isBusy}
          >
            <RefreshIcon className="size-4" />
          </button>
        ) : (
          <span className="size-8 shrink-0" aria-hidden="true" />
        ))}

      {canDelete && (
        <button
          type="button"
          title="حذف الاشتراك"
          aria-label="حذف الاشتراك"
          className={`${ACTION_BUTTON_CLASS} border-app-red/25 bg-app-red/10 text-app-red hover:border-app-red/60 hover:bg-app-red/20`}
          onClick={(event) => {
            event.stopPropagation();
            onDelete(subscription);
          }}
          disabled={isBusy}
        >
          <TrashIcon className="size-4" />
        </button>
      )}

      {canView && (
        <button
          type="button"
          title="عرض التفاصيل"
          aria-label="عرض التفاصيل"
          className={`${ACTION_BUTTON_CLASS} border-app-line bg-slate-500/15 text-app-muted-light hover:border-slate-400/50 hover:bg-slate-500/25 hover:text-app-text`}
          onClick={(event) => {
            event.stopPropagation();
            onView(subscription);
          }}
        >
          <MoreVerticalIcon />
        </button>
      )}
    </div>
  );
}

/**
 * Renders the subscription list, filters, statistics, and detail drawer.
 */
export default function SubscriptionsClient({ initialData }) {
  const { can } = usePermissions();
  const canCreate = can("player-subscription.create");
  const canView = can("player-subscription.view");
  const canUpdate = can("player-subscription.update");
  const canDelete = can("player-subscription.delete");
  const canFreeze = can("player-subscription.freeze");
  const canUnfreeze = can("player-subscription.unfreeze");
  const canCancel = can("player-subscription.cancel");
  const canRenew = can("player-subscription.renew");
  const {
    search,
    setSearch,
    status,
    setStatus,
    period,
    setPeriod,
    activityTypeId,
    setActivityTypeId,
    branchFilter,
    setBranchFilter,
    selectedSubscriptionId,
    setSelectedSubscriptionId,
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
    pagination,
    totalResults,
    stats,
    errorMessage,
    branches,
    activityTypes,
    isActivityTypesLoading,
    deleteConfirmation,
    setDeleteConfirmation,
    isFreezing,
    isUnfreezing,
    isCancelling,
    isRenewing,
    isDeleting,
    renewalSubscription,
    renewalPlans,
    isRenewalPlansLoading,
    renewalPlansErrorMessage,
    renewalErrorMessage,
    deleteConfirmOpen,
    itemToDelete,
    isRefunded,
    setIsRefunded,
    deleteReason,
    setDeleteReason,
    handleFreeze,
    handleUnfreeze,
    handleCancel,
    openRenewal,
    closeRenewal,
    handleRenew,
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    closeDrawer,
  } = useSubscriptions({ initialData });

  const subscriptionColumns = useMemo(
    () => [
      {
        key: "row_number",
        label: "#",
        align: "center",
        type: "rowNumber",
        sortable: false,
      },
      {
        key: "member",
        label: "العضو",
        align: "center",
        sortValue: (subscription) => {
          const member = subscription.member || {};
          const person = member.person || {};
          return person.full_name || getMemberAccountName(member, subscription);
        },
        render: (_, subscription) => <SubscriptionMemberIdentity subscription={subscription} />,
      },
      {
        key: "plan",
        label: "الخطة",
        align: "center",
        sortValue: (subscription) => {
          const plan = subscription.plan || {};
          return typeof plan.name === "string" ? plan.name : plan.name?.ar || plan.name?.en || "";
        },
        render: (_, subscription) => {
          const plan = subscription.plan || {};
          const planName =
            typeof plan.name === "string" ? plan.name : plan.name?.ar || plan.name?.en || "-";

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
      {
        key: "created_by",
        label: "الموظف المُضيف",
        align: "center",
        sortValue: (subscription) => getSubscriptionCreatorName(subscription) || "",
        render: (_, subscription) => (
          <span className="truncate font-medium text-app-text">
            {getSubscriptionCreatorName(subscription) || "-"}
          </span>
        ),
      },
      {
        key: "paid_amount",
        label: "الصافي / المدفوع",
        align: "center",
        sortValue: (subscription) => Number(subscription.paid_amount || 0),
        render: (_, subscription) => <SubscriptionAmountBadges subscription={subscription} />,
      },
      {
        key: "receipts",
        label: "الإيصالات",
        align: "center",
        sortable: false,
        render: (_, subscription) => <SubscriptionReceiptBadges subscription={subscription} />,
      },
      {
        key: "dates",
        label: "تاريخ الصلاحية",
        align: "center",
        sortValue: (subscription) =>
          subscription.created_at || subscription.start_date || subscription.end_date || "",
        render: (_, subscription) => (
          <div className="flex flex-col items-center gap-1 text-center text-[11px]">
            <p className="text-app-muted-light">{formatDate(subscription.start_date)}</p>
            <p className="text-app-yellow">{formatDate(subscription.end_date)}</p>
            {subscription.is_expiring_soon && (
              <span className="status-warning inline-flex rounded-md px-2 py-0.5 text-[10px] font-medium">
                تنتهي قريباً
              </span>
            )}
          </div>
        ),
      },
      {
        key: "status",
        label: "الحالة",
        align: "center",
        sortValue: (subscription) => subscription.status || "",
        render: (value) => <SubscriptionStatusBadge status={value} />,
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, subscription) => (
          <SubscriptionTableActions
            subscription={subscription}
            canView={canView}
            canUpdate={canUpdate}
            canDelete={canDelete}
            canRenew={canRenew}
            isBusy={isDeleting || isRenewing}
            onView={(item) => setSelectedSubscriptionId(item.id)}
            onDelete={handleDelete}
            onRenew={openRenewal}
          />
        ),
      },
    ],
    [
      canDelete,
      canRenew,
      canUpdate,
      canView,
      handleDelete,
      isDeleting,
      isRenewing,
      openRenewal,
      setSelectedSubscriptionId,
    ],
  );

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({ value: String(b.id), label: formatLocalizedName(b.name) })),
    ],
    [branches],
  );

  const activityTypeOptions = useMemo(
    () => [
      { value: "all", label: "كل أنواع النشاط" },
      ...activityTypes.map((activityType) => ({
        value: String(activityType.id),
        label: formatLocalizedName(activityType.name) || `نوع النشاط #${activityType.id}`,
      })),
    ],
    [activityTypes],
  );

  return (
    <div className="space-y-6" dir="rtl">
      <PageHeader
        eyebrow="إدارة النادي"
        title="اشتراكات الأعضاء"
        subtitle="متابعة خطط الأعضاء، حالة الاشتراك، التجميدات، وإلغاء وتعديل المدفوعات."
        action={
          <div className="flex flex-wrap gap-3">
            <Button tone="outline" className="h-10 px-4" onClick={refetch} disabled={isFetching}>
              {isFetching ? "جاري التحديث" : "تحديث البيانات"}
            </Button>
            {canCreate && (
              <Button
                href="/management/offers"
                tone="outline"
                className="h-10 px-4 text-xs font-semibold border-app-yellow/50 text-app-yellow hover:bg-app-yellow/10 transition-colors"
              >
                🎁 باقات العروض
              </Button>
            )}
            {canCreate && (
              <Button
                href="/management/subscriptions/create"
                icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
                style={{ color: "#000000" }}
              >
                تسجيل اشتراك
              </Button>
            )}
          </div>
        }
      />

      <StatsGrid items={stats} />

      <DataTable
        title="قائمة اشتراكات الأعضاء"
        columns={subscriptionColumns}
        rows={filteredSubscriptions}
        minWidth="0"
        tableColumns={TABLE_GRID_COLUMNS}
        desktopScrollable={false}
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
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={refetch}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد اشتراكات مطابقة للبحث الحالي."
          )
        }
        desktopRowsClassName="divide-y divide-app-line/70"
        rowClassName="gap-2 !rounded-none !border-0 !bg-transparent px-3 !py-3 hover:!bg-app-card-hover/50"
        headerClassName="gap-2 rounded-t-lg bg-app-card-soft/70 px-3"
        onRowClick={
          canView ? (subscription) => setSelectedSubscriptionId(subscription.id) : undefined
        }
        getRowKey={(subscription) => subscription.id}
        currentPage={pagination.currentPage}
        totalPages={pagination.lastPage}
        totalItems={pagination.total}
        pageSize={pagination.perPage}
        pageSizeOptions={PAGE_SIZE_OPTIONS}
        onPageChange={pagination.setPage}
        onPageSizeChange={pagination.setPerPage}
        toolbarActions={
          <div className="grid w-full grid-cols-2 gap-3 sm:grid-cols-[minmax(0,1fr)_9rem_9rem_10rem] sm:items-center">
            <label className="relative col-span-2 block min-w-0 sm:col-span-1">
              <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-white outline-none transition focus:border-app-yellow/70"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="بحث بالاسم، اسم الحساب، الهاتف أو البريد"
                type="search"
              />
            </label>

            <Dropdown
              className="w-full min-w-0 bg-app-card-soft text-white"
              icon={FilterIcon}
              value={status}
              options={SUBSCRIPTION_STATUS_OPTIONS}
              onChange={setStatus}
              compact
            />

            <Dropdown
              className="w-full min-w-0 bg-app-card-soft text-white"
              icon={FilterIcon}
              value={period}
              options={SUBSCRIPTION_PERIOD_OPTIONS}
              onChange={setPeriod}
              compact
            />

            <Dropdown
              className="col-span-2 w-full min-w-0 bg-app-card-soft text-white sm:col-span-1"
              icon={FilterIcon}
              value={activityTypeId}
              options={activityTypeOptions}
              onChange={setActivityTypeId}
              disabled={isActivityTypesLoading}
              ariaLabel="تصفية حسب نوع النشاط"
              compact
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">{totalResults.toLocaleString("ar")}</span>
          </p>
        }
      />

      <Drawer
        open={Boolean(selectedSubscriptionId)}
        onClose={closeDrawer}
        title="تفاصيل الاشتراك"
        subtitle={
          selectedSubscription?.member?.person?.full_name ||
          (selectedSubscriptionId ? `رقم الاشتراك ${selectedSubscriptionId}` : "")
        }
      >
        <SubscriptionDetails
          subscription={selectedSubscription}
          error={subscriptionDetailError}
          isLoading={isSubscriptionDetailLoading || isSubscriptionDetailFetching}
          onRetry={refetchSubscriptionDetail}
          onFreeze={canFreeze ? handleFreeze : undefined}
          onUnfreeze={canUnfreeze ? handleUnfreeze : undefined}
          onCancel={canCancel ? handleCancel : undefined}
          isFreezing={isFreezing}
          isUnfreezing={isUnfreezing}
          isCancelling={isCancelling}
        />
      </Drawer>

      <ConfirmDialog
        open={canDelete && deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف الاشتراك"
        message={`هل أنت متأكد من رغبتك في حذف اشتراك "${
          selectedSubscription?.plan?.name || "هذا اللاعب"
        }"؟`}
        requiredConfirmation="delete"
        confirmationValue={deleteConfirmation}
        onConfirmationChange={setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={isDeleting}
      >
        <div className="space-y-4 rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
          <label className="flex items-start gap-3 cursor-pointer select-none">
            <input
              type="checkbox"
              checked={isRefunded}
              onChange={(e) => {
                const checked = e.target.checked;
                setIsRefunded(checked);
                if (checked && !deleteReason) {
                  setDeleteReason("طلب اللاعب إلغاء واسترداد المبلغ");
                }
              }}
              className="mt-1 size-4 rounded border-app-line bg-black/40 text-app-yellow accent-app-yellow focus:ring-1 focus:ring-app-yellow focus:ring-offset-0 cursor-pointer"
            />
            <div className="flex-1">
              <span className="block text-sm font-medium text-app-text">
                إعادة سعر الاشتراك للاعب (استرداد المبلغ - Refund)
              </span>
              <span className="mt-1 block text-xs leading-relaxed text-app-muted-light">
                {isRefunded
                  ? "سيتم حذف سجل التوزيع المالي (subscription_revenue_splits) واعتبار المبلغ مستردًا للاعب."
                  : "يبقى السجل المالي وتوزيع الإيرادات محفوظاً في النظام بدون استرداد مالي."}
              </span>
            </div>
          </label>

          <div className="pt-3 border-t border-app-line/50">
            <label className="block text-xs font-medium text-app-muted-light mb-1.5">
              سبب الحذف / الاسترداد (اختياري):
            </label>
            <input
              type="text"
              className="app-input h-10 w-full px-3 text-right text-sm text-app-text outline-none transition focus:border-app-yellow/70 bg-black/25"
              placeholder="مثال: طلب اللاعب إلغاء واسترداد المبلغ"
              value={deleteReason}
              onChange={(e) => setDeleteReason(e.target.value)}
              disabled={isDeleting}
            />
          </div>
        </div>
      </ConfirmDialog>

      <RenewSubscriptionModal
        open={canRenew && Boolean(renewalSubscription)}
        subscription={renewalSubscription}
        plans={renewalPlans}
        isPlansLoading={isRenewalPlansLoading}
        plansErrorMessage={renewalPlansErrorMessage}
        onClose={closeRenewal}
        onSubmit={handleRenew}
        isLoading={isRenewing}
        errorMessage={renewalErrorMessage}
      />
    </div>
  );
}

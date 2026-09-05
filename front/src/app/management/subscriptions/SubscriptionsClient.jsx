"use client";

import SubscriptionDetails from "./SubscriptionDetails";
import SubscriptionStatusBadge from "./SubscriptionStatusBadge";
import { useMemo } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import StatsGrid from "@/components/ui/StatsGrid";
import { FilterIcon, SearchIcon, PlusIcon } from "@/components/icons/Icons";
import { useSubscriptions } from "./useSubscriptions";
import { formatDate, formatLocalizedName } from "@/lib/utils";
import { SUBSCRIPTION_STATUS_OPTIONS } from "./subscriptionConstants";
import { formatSubscriptionMoney } from "./subscriptionUtils";
import { usePermissions } from "@/lib/PermissionContext";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";

const TABLE_GRID_COLUMNS = "minmax(180px,1.25fr) minmax(160px,1fr) 88px 128px 88px 90px";

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
  const {
    search,
    setSearch,
    status,
    setStatus,
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
    deleteConfirmation,
    setDeleteConfirmation,
    isFreezing,
    isUnfreezing,
    isCancelling,
    isDeleting,
    deleteConfirmOpen,
    itemToDelete,
    isRefunded,
    setIsRefunded,
    deleteReason,
    setDeleteReason,
    handleFreeze,
    handleUnfreeze,
    handleCancel,
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    closeDrawer,
  } = useSubscriptions({ initialData });

  const subscriptionColumns = useMemo(
    () => [
      {
        key: "member",
        label: "العضو",
        align: "center",
        sortValue: (subscription) => {
          const member = subscription.member || {};
          const person = member.person || {};
          return person.full_name || member.member_number || "";
        },
        render: (_, subscription) => {
          const member = subscription.member || {};
          const person = member.person || {};

          return (
            <div className="min-w-0 text-center">
              <p className="truncate text-sm font-medium text-app-text">
                {person.full_name || "-"}
              </p>
              <p className="mt-1 truncate text-[11px] text-app-muted-light" dir="ltr">
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
        key: "remaining_amount",
        label: "المتبقي",
        align: "center",
        sortValue: (subscription) => Number(subscription.remaining_amount || 0),
        render: (value) => (
          <span className="font-medium text-app-red">{formatSubscriptionMoney(value)}</span>
        ),
      },
      {
        key: "dates",
        label: "تاريخ الصلاحية",
        align: "center",
        sortValue: (subscription) => subscription.start_date || subscription.end_date || "",
        render: (_, subscription) => (
          <div className="text-center text-[11px]">
            <p className="text-app-muted-light">{formatDate(subscription.start_date)}</p>
            <p className="mt-0.5 text-app-yellow">{formatDate(subscription.end_date)}</p>
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
          <RowActions
            disabled={isDeleting}
            editHref={
              canUpdate
                ? `/management/subscriptions/create?mode=edit&id=${subscription.id}`
                : undefined
            }
            editTitle="تعديل الاشتراك"
            onDelete={canDelete ? () => handleDelete(subscription) : undefined}
          />
        ),
      },
    ],
    [canDelete, canUpdate, handleDelete, isDeleting],
  );

  const branchOptions = useMemo(
    () => [
      { value: "all", label: "كل الفروع" },
      ...branches.map((b) => ({ value: String(b.id), label: formatLocalizedName(b.name) })),
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
          <div className="flex flex-wrap gap-3">
            <Button tone="outline" className="h-10 px-4" onClick={refetch} disabled={isFetching}>
              {isFetching ? "جاري التحديث" : "تحديث البيانات"}
            </Button>
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
        minWidth="930px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        defaultSortColumn="member"
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
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
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
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
            <label className="relative block w-full sm:w-80 md:w-96">
              <SearchIcon className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-app-muted-light" />
              <input
                className="app-input h-10 w-full bg-app-card-soft ps-9 pe-3 text-right text-sm text-white outline-none transition focus:border-app-yellow/70"
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
              options={SUBSCRIPTION_STATUS_OPTIONS}
              onChange={setStatus}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {totalResults.toLocaleString("ar")}
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
    </div>
  );
}

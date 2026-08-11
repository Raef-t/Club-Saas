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

const TABLE_GRID_COLUMNS = "minmax(180px,1.25fr) minmax(160px,1fr) 88px 128px 88px 90px";

/**
 * Renders the subscription list, filters, statistics, and detail drawer.
 */
export default function SubscriptionsClient({ initialData }) {
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
    stats,
    errorMessage,
    branches,
    isFreezing,
    isUnfreezing,
    isCancelling,
    isDeleting,
    deleteConfirmOpen,
    itemToDelete,
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
        render: (value) => (
          <span className="font-medium text-app-red">{formatSubscriptionMoney(value)}</span>
        ),
      },
      {
        key: "dates",
        label: "تاريخ الصلاحية",
        align: "center",
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
        render: (value) => <SubscriptionStatusBadge status={value} />,
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        render: (_, subscription) => (
          <RowActions
            disabled={isDeleting}
            editHref={`/management/subscriptions/create?mode=edit&id=${subscription.id}`}
            editTitle="تعديل الاشتراك"
            onDelete={() => handleDelete(subscription)}
          />
        ),
      },
    ],
    [handleDelete, isDeleting],
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
        onRowClick={(subscription) => setSelectedSubscriptionId(subscription.id)}
        getRowKey={(subscription) => subscription.id}
        totalPages={0}
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
          (selectedSubscriptionId ? `رقم الاشتراك ${selectedSubscriptionId}` : "")
        }
      >
        <SubscriptionDetails
          subscription={selectedSubscription}
          error={subscriptionDetailError}
          isLoading={isSubscriptionDetailLoading || isSubscriptionDetailFetching}
          onRetry={refetchSubscriptionDetail}
          onFreeze={handleFreeze}
          onUnfreeze={handleUnfreeze}
          onCancel={handleCancel}
          isFreezing={isFreezing}
          isUnfreezing={isUnfreezing}
          isCancelling={isCancelling}
        />
      </Drawer>

      <ConfirmDialog
        open={deleteConfirmOpen}
        onClose={closeDeleteConfirm}
        onConfirm={confirmDelete}
        title="تأكيد حذف الاشتراك"
        message={`هل أنت متأكد من رغبتك في حذف الاشتراك${itemToDelete?.member?.person?.full_name ? ` الخاص برقم العضوية (${itemToDelete.member.member_number || itemToDelete.id})` : ""}؟ لا يمكن التراجع عن هذا الإجراء.`}
        isLoading={isDeleting}
      />
    </div>
  );
}

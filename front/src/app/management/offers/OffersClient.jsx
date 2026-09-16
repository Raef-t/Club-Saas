"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Drawer from "@/components/ui/Drawer";
import RowActions from "@/components/ui/RowActions";
import SearchInput from "@/components/ui/SearchInput";
import Dropdown from "@/components/ui/Dropdown";
import StatsGrid from "@/components/ui/StatsGrid";
import DetailItem from "@/components/ui/DetailItem";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { PlusIcon, FilterIcon } from "@/components/icons/Icons";
import { useGetOffersQuery, useDeleteOfferMutation } from "@/lib/api/offersApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatMoney, formatLocalizedName } from "@/lib/utils";
import { usePermissions } from "@/lib/PermissionContext";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";

const TABLE_GRID_COLUMNS =
  "minmax(180px,1.2fr) minmax(260px,2fr) 110px 120px 105px 110px";

const STATUS_FILTER_OPTIONS = [
  { value: "all", label: "جميع الحالات" },
  { value: "available", label: "المتاحة للاشتراك فقط" },
  { value: "inactive", label: "المعطلة أو المكتملة" },
];

function getPlanCapacity(plan) {
  const isUnlimited = Boolean(
    plan.is_unlimited_subscribers ||
    plan.max_subscribers === 0 ||
    plan.max_subscribers === null ||
    plan.max_subscribers === undefined
  );

  if (isUnlimited) {
    return { isUnlimited: true, isFull: false, availableSeats: Infinity, label: "غير محدود" };
  }

  let slots = 0;
  if (plan.available_slots !== undefined && plan.available_slots !== null) {
    slots = Math.max(0, Number(plan.available_slots));
  } else {
    const max = Number(plan.max_subscribers) || 0;
    const cur = Number(plan.current_subscribers) || 0;
    slots = Math.max(0, max - cur);
  }

  const isFull = slots <= 0;
  return {
    isUnlimited: false,
    isFull,
    availableSeats: slots,
    label: isFull ? "مكتمل" : `${slots} مقعد`,
  };
}

function OfferStatusBadge({ offer }) {
  if (!offer.is_active) {
    return (
      <span className="inline-flex min-w-20 justify-center rounded-md border border-rose-500/30 bg-rose-500/15 px-2.5 py-1 text-xs font-medium text-rose-400">
        معطل
      </span>
    );
  }

  if (offer.end_date && new Date(offer.end_date) < new Date().setHours(0, 0, 0, 0)) {
    return (
      <span className="inline-flex min-w-20 justify-center rounded-md border border-rose-500/30 bg-rose-500/15 px-2.5 py-1 text-xs font-medium text-rose-400">
        منتهي الصلاحية
      </span>
    );
  }

  if (offer.is_available) {
    const slotsText =
      offer.offer_type !== "single_choice" &&
      offer.available_slots !== null &&
      offer.available_slots !== undefined
        ? ` (${offer.available_slots} مقعد)`
        : "";
    return (
      <span className="inline-flex min-w-20 justify-center rounded-md border border-emerald-500/30 bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-400">
        متاح{slotsText}
      </span>
    );
  }

  return (
    <span className="inline-flex min-w-20 justify-center rounded-md border border-amber-500/30 bg-amber-500/15 px-2.5 py-1 text-xs font-medium text-amber-400">
      مكتمل السعة
    </span>
  );
}

export default function OffersClient() {
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const { can, isSuperAdmin } = usePermissions();

  const canCreate = isSuperAdmin || can("offer.create") || can("subscription-plan.create");
  const canUpdate = isSuperAdmin || can("offer.update") || can("subscription-plan.update");
  const canDelete = isSuperAdmin || can("offer.delete") || can("subscription-plan.delete");

  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  // Selection & Dialog states
  const [selectedOffer, setSelectedOffer] = useState(null);
  const [deletingOffer, setDeletingOffer] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");

  const [deleteOffer, { isLoading: isDeleting }] = useDeleteOfferMutation();

  const queryParams = useMemo(() => {
    const params = { all: true };
    if (selectedBranchId && selectedBranchId !== "all") {
      params.branch_id = selectedBranchId;
    }
    return params;
  }, [selectedBranchId]);

  const { data, isLoading, isFetching, isError, refetch } = useGetOffersQuery(queryParams);

  const offers = useMemo(() => {
    let list = [];
    if (Array.isArray(data?.data?.data)) list = data.data.data;
    else if (Array.isArray(data?.data)) list = data.data;
    else if (Array.isArray(data)) list = data;
    return list;
  }, [data]);

  // Client-side filtering
  const filteredOffers = useMemo(() => {
    return offers.filter((offer) => {
      if (searchTerm.trim()) {
        const query = searchTerm.toLowerCase();
        const matchesName = offer.name?.toLowerCase().includes(query);
        const matchesDesc = offer.description?.toLowerCase().includes(query);
        const matchesPlan = offer.plans?.some((p) => p.name?.toLowerCase().includes(query));
        if (!matchesName && !matchesDesc && !matchesPlan) return false;
      }

      if (statusFilter === "available") {
        if (!offer.is_available) return false;
      } else if (statusFilter === "inactive") {
        if (offer.is_active && offer.is_available) return false;
      }

      return true;
    });
  }, [offers, searchTerm, statusFilter]);

  // Statistics for StatsGrid
  const stats = useMemo(() => {
    const total = offers.length;
    const available = offers.filter((o) => o.is_available).length;
    const inactive = offers.filter((o) => !o.is_active || !o.is_available).length;
    const totalActiveSubscribers = offers.reduce(
      (sum, o) => sum + (Number(o.active_subscribers_count) || 0),
      0
    );

    return [
      {
        title: "إجمالي العروض",
        value: total.toLocaleString("ar"),
        helper: "جميع العروض الترويجية المسجلة",
        tone: "blue",
        compact: true,
        onClick: () => setStatusFilter("all"),
        active: statusFilter === "all",
      },
      {
        title: "العروض المتاحة",
        value: available.toLocaleString("ar"),
        helper: "جاهزة لاشتراك اللاعبين",
        tone: "green",
        compact: true,
        onClick: () => setStatusFilter(statusFilter === "available" ? "all" : "available"),
        active: statusFilter === "available",
      },
      {
        title: "المشتركون النشطون",
        value: totalActiveSubscribers.toLocaleString("ar"),
        helper: "لاعب مشترك بالعروض",
        tone: "yellow",
        compact: true,
      },
      {
        title: "المعطلة أو المكتملة",
        value: inactive.toLocaleString("ar"),
        helper: "غير متاحة حالياً",
        tone: "red",
        compact: true,
        onClick: () => setStatusFilter(statusFilter === "inactive" ? "all" : "inactive"),
        active: statusFilter === "inactive",
      },
    ];
  }, [offers, statusFilter]);

  async function handleConfirmDelete() {
    if (!deletingOffer) return;

    try {
      await deleteOffer({ id: deletingOffer.id, confirm: "delete" }).unwrap();
      toast.success("تم حذف العرض الترويجي بنجاح!");
      setDeletingOffer(null);
      setDeleteConfirmation("");
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر حذف العرض الترويجي."));
    }
  }

  // DataTable columns definition متطابق مع جدول الفعاليات وبدون تاريخ
  const columns = useMemo(
    () => [
      {
        key: "name",
        label: "العرض الترويجي",
        align: "center",
        sortValue: (offer) => offer.name || "",
        render: (_, offer) => (
          <div className="min-w-0 text-center space-y-1">
            <p className="truncate text-sm font-medium text-app-text">{offer.name}</p>
            <div className="flex items-center justify-center gap-1.5 flex-wrap">
              {offer.offer_type === "single_choice" ? (
                <span className="inline-block rounded-md bg-purple-500/15 border border-purple-500/30 px-2 py-0.5 text-[10px] text-purple-300 font-medium">
                  🏷️ يختار المشترك فعالية واحدة
                </span>
              ) : (
                <span className="inline-block rounded-md bg-blue-500/15 border border-blue-500/30 px-2 py-0.5 text-[10px] text-blue-300 font-medium">
                  📦 باقة مجمعة
                </span>
              )}
            </div>
            {offer.description ? (
              <p className="truncate text-[11px] text-app-muted-light">{offer.description}</p>
            ) : null}
          </div>
        ),
      },
      {
        key: "plans",
        label: "الفعاليات المشمولة",
        align: "start",
        sortable: false,
        render: (_, offer) => {
          const plansList = offer.plans || [];
          if (plansList.length === 0) return <span className="text-app-muted-light">-</span>;

          if (offer.offer_type === "single_choice") {
            return (
              <div className="w-full flex flex-col justify-center divide-y divide-app-line/40">
                {plansList.map((plan) => {
                  const displayName = formatLocalizedName(plan.name) || plan.name;
                  return (
                    <div
                      key={plan.id}
                      className="h-10 flex items-center justify-start text-right px-1"
                    >
                      <span className="font-medium text-white truncate text-xs" title={displayName}>
                        {displayName}
                      </span>
                    </div>
                  );
                })}
              </div>
            );
          }

          return (
            <div className="flex flex-wrap justify-center gap-1">
              {plansList.map((plan) => (
                <span
                  key={plan.id}
                  className="inline-block rounded bg-white/10 px-2 py-0.5 text-[11px] text-app-muted-light"
                >
                  {formatLocalizedName(plan.name) || plan.name}
                </span>
              ))}
            </div>
          );
        },
      },
      {
        key: "price",
        label: "السعر",
        align: "center",
        sortValue: (offer) => Number(offer.price || 0),
        render: (value, offer) => {
          if (offer.offer_type === "single_choice") {
            const plansList = offer.plans || [];
            if (plansList.length === 0) {
              return (
                <span className="font-medium text-app-yellow text-xs">
                  {formatMoney(value)}
                </span>
              );
            }

            return (
              <div className="w-full flex flex-col justify-center divide-y divide-app-line/40">
                {plansList.map((plan) => (
                  <div
                    key={plan.id}
                    className="h-10 flex items-center justify-center"
                  >
                    <span className="font-medium text-app-yellow text-xs">
                      {formatMoney(offer.price)}
                    </span>
                  </div>
                ))}
              </div>
            );
          }

          return (
            <div className="text-center">
              <span className="font-medium text-app-yellow block">{formatMoney(value)}</span>
              <span className="text-[10px] text-app-muted-light">للباقة كاملة</span>
            </div>
          );
        },
      },
      {
        key: "discount",
        label: "التوفير",
        align: "center",
        sortable: false,
        render: (_, offer) => {
          if (offer.offer_type === "single_choice") {
            const plansList = offer.plans || [];
            if (plansList.length === 0) {
              return <span className="text-xs text-app-muted-light">-</span>;
            }

            return (
              <div className="w-full flex flex-col justify-center divide-y divide-app-line/40">
                {plansList.map((plan) => {
                  const regularPrice = Number(plan.base_price ?? plan.price) || 0;
                  const offerPrice = Number(offer.price) || 0;
                  const savings = regularPrice > offerPrice ? regularPrice - offerPrice : 0;

                  return (
                    <div
                      key={plan.id}
                      className="h-10 flex items-center justify-center"
                    >
                      {savings > 0 ? (
                        <span className="font-semibold text-emerald-400 text-xs">
                          {formatMoney(savings)}
                        </span>
                      ) : (
                        <span className="text-xs text-app-muted-light">-</span>
                      )}
                    </div>
                  );
                })}
              </div>
            );
          }

          const regularPrice =
            offer.regular_price ||
            (offer.plans || []).reduce((s, p) => s + (Number(p.base_price) || 0), 0);
          const savings = regularPrice - Number(offer.price || 0);

          if (savings > 0) {
            return (
              <div className="text-center">
                <span className="font-medium text-xs text-emerald-400 block">
                  {formatMoney(savings)}
                </span>
                <span className="text-[10px] text-app-muted-light">للباقة</span>
              </div>
            );
          }
          return <span className="text-xs text-app-muted-light">-</span>;
        },
      },
      {
        key: "status",
        label: "الحالة",
        align: "center",
        sortValue: (offer) => (offer.is_available ? 1 : 0),
        render: (_, offer) => {
          if (offer.offer_type === "single_choice") {
            const plansList = offer.plans || [];
            if (plansList.length === 0) {
              return <OfferStatusBadge offer={offer} />;
            }

            return (
              <div className="w-full flex flex-col justify-center divide-y divide-app-line/40">
                {plansList.map((plan) => {
                  const capacity = getPlanCapacity(plan);

                  return (
                    <div
                      key={plan.id}
                      className="h-10 flex items-center justify-center"
                    >
                      <span
                        className={`inline-flex min-w-[70px] justify-center rounded-md px-2 py-0.5 text-[11px] font-medium border ${
                          !offer.is_active
                            ? "border-rose-500/30 bg-rose-500/15 text-rose-400"
                            : capacity.isFull
                            ? "border-amber-500/30 bg-amber-500/15 text-amber-400"
                            : capacity.isUnlimited
                            ? "border-app-line bg-white/10 text-app-muted-light"
                            : "border-emerald-500/30 bg-emerald-500/15 text-emerald-400"
                        }`}
                      >
                        {capacity.label}
                      </span>
                    </div>
                  );
                })}
              </div>
            );
          }

          return <OfferStatusBadge offer={offer} />;
        },
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, offer) => {
          if (offer.offer_type === "single_choice") {
            const plansList = offer.plans || [];
            if (plansList.length === 0) {
              return (
                <div
                  className="flex items-center justify-center gap-2"
                  onClick={(event) => event.stopPropagation()}
                >
                  <RowActions
                    disabled={isDeleting}
                    editHref={
                      canUpdate
                        ? `/management/offers/create?mode=edit&id=${offer.id}`
                        : undefined
                    }
                    onDelete={canDelete ? () => setDeletingOffer(offer) : undefined}
                    className="gap-2"
                  />
                </div>
              );
            }

            return (
              <div
                className="w-full flex flex-col justify-center divide-y divide-app-line/40"
                onClick={(event) => event.stopPropagation()}
              >
                {plansList.map((plan) => (
                  <div
                    key={plan.id}
                    className="h-10 flex items-center justify-center gap-2"
                  >
                    <RowActions
                      disabled={isDeleting}
                      editHref={
                        canUpdate
                          ? `/management/offers/create?mode=edit&id=${offer.id}`
                          : undefined
                      }
                      onDelete={canDelete ? () => setDeletingOffer(offer) : undefined}
                      className="gap-2"
                    />
                  </div>
                ))}
              </div>
            );
          }

          return (
            <div
              className="flex items-center justify-center gap-2"
              onClick={(event) => event.stopPropagation()}
            >
              <RowActions
                disabled={isDeleting}
                editHref={
                  canUpdate
                    ? `/management/offers/create?mode=edit&id=${offer.id}`
                    : undefined
                }
                onDelete={canDelete ? () => setDeletingOffer(offer) : undefined}
                className="gap-2"
              />
            </div>
          );
        },
      },
    ],
    [canDelete, canUpdate, isDeleting]
  );

  return (
    <div className="space-y-6" dir="rtl">
      {/* Top Header */}
      <PageHeader
        eyebrow="إدارة النادي"
        title="العروض الترويجية"
        action={
          canCreate ? (
            <Button
              href="/management/offers/create"
              icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
              style={{ color: "#000000" }}
            >
              إنشاء عرض ترويجي
            </Button>
          ) : null
        }
      />

      {/* Stats Grid */}
      <StatsGrid items={stats} />

      {/* DataTable بتصميم مطابق لصفحة الفعاليات */}
      <DataTable
        title="قائمة العروض الترويجية"
        columns={columns}
        rows={filteredOffers}
        minWidth="930px"
        tableColumns={TABLE_GRID_COLUMNS}
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        defaultSortColumn="name"
        isLoading={isLoading}
        emptyMessage={
          isError ? (
            <div className="space-y-3 text-center">
              <p className="text-app-red">تعذر تحميل العروض الترويجية.</p>
              <Button tone="outline" className="h-9 px-3 text-xs" onClick={() => refetch()}>
                إعادة المحاولة
              </Button>
            </div>
          ) : (
            "لا توجد عروض ترويجية مطابقة للبحث الحالي."
          )
        }
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        onRowClick={(offer) => setSelectedOffer(offer)}
        getRowKey={(offer) => offer.id}
        pageSize={15}
        pageSizeOptions={PAGE_SIZE_OPTIONS}
        toolbarActions={
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:flex-wrap">
            <div className="w-full sm:w-80 md:w-96">
              <SearchInput
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="بحث باسم العرض أو الفعالية..."
                className="w-full"
              />
            </div>

            <Dropdown
              className="min-w-52 bg-app-card-soft text-white"
              icon={FilterIcon}
              value={statusFilter}
              options={STATUS_FILTER_OPTIONS}
              onChange={setStatusFilter}
            />
          </div>
        }
        toolbarMeta={
          <p className="text-sm text-app-muted-light">
            النتائج:{" "}
            <span className="font-medium text-app-text">
              {filteredOffers.length.toLocaleString("ar")}
            </span>
          </p>
        }
      />

      {/* Details Drawer بدون زر تعديل وبدون تواريخ */}
      <Drawer
        open={Boolean(selectedOffer)}
        onClose={() => setSelectedOffer(null)}
        title="تفاصيل العرض الترويجي"
        subtitle={selectedOffer?.name}
      >
        {selectedOffer && (
          <div className="space-y-6">
            {/* Top Banner */}
            <div className="rounded-xl border border-app-line bg-app-card-soft/70 p-4 text-right">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <h3 className="truncate text-lg font-bold text-app-text">
                    {selectedOffer.name}
                  </h3>
                  {selectedOffer.description && (
                    <p className="mt-1 text-xs text-app-muted-light leading-relaxed">
                      {selectedOffer.description}
                    </p>
                  )}
                </div>
                <OfferStatusBadge offer={selectedOffer} />
              </div>
            </div>

            {/* Details Grid */}
            <section className="grid gap-3 sm:grid-cols-2">
              <DetailItem
                label={selectedOffer.offer_type === "single_choice" ? "سعر الفعالية المخفض" : "سعر الباقة الإجمالي"}
                value={formatMoney(selectedOffer.price)}
                tone="yellow"
              />
              <DetailItem
                label="نوع وهيكل العرض"
                value={selectedOffer.offer_type === "single_choice" ? "🏷️ يختار المشترك فعالية واحدة (Single Choice)" : "📦 باقة فعاليات مجمعة (Bundle)"}
              />
              <DetailItem
                label="صلاحية التواريخ"
                value={
                  selectedOffer.end_date
                    ? `من ${selectedOffer.start_date || "الآن"} إلى ${selectedOffer.end_date}`
                    : "غير محدد بتاريخ"
                }
              />
              <DetailItem
                label="المشتركون النشطون"
                value={`${selectedOffer.active_subscribers_count || 0} مشترك`}
              />
              <DetailItem
                label="حالة التفعيل"
                value={selectedOffer.is_active ? "مفعل" : "معطل"}
                tone={selectedOffer.is_active ? "green" : "red"}
              />
            </section>

            {/* Plans included in the offer */}
            <div className="space-y-3">
              <h4 className="text-sm font-semibold text-white">
                {selectedOffer.offer_type === "single_choice"
                  ? "الفعاليات المؤهلة للعرض (تختار المشتركة فعالية واحدة منها):"
                  : "الفعاليات المشمولة في الباقة (تُسجل المشتركة فيها جميعاً):"}
              </h4>
              <div className="space-y-2">
                {(selectedOffer.plans || []).map((plan) => {
                  const capacity = getPlanCapacity(plan);
                  const regularPrice = Number(plan.base_price ?? plan.price) || 0;
                  const offerPrice = Number(selectedOffer.price) || 0;
                  const savings = regularPrice > offerPrice ? regularPrice - offerPrice : 0;
                  const displayName = formatLocalizedName(plan.name) || plan.name;

                  return (
                    <div
                      key={plan.id}
                      className="flex items-center justify-between rounded-xl border border-app-line bg-app-card-soft/50 p-3"
                    >
                      <div className="text-right">
                        <div className="flex items-center gap-2">
                          <p className="text-sm font-medium text-app-text">{displayName}</p>
                          <span
                            className={`rounded px-2 py-0.5 text-[10px] font-medium ${
                              capacity.isFull
                                ? "rounded bg-amber-500/20 border border-amber-500/40 text-amber-300"
                                : capacity.isUnlimited
                                ? "rounded bg-white/10 text-app-muted-light"
                                : "rounded bg-emerald-500/20 border border-emerald-500/40 text-emerald-300"
                            }`}
                          >
                            {capacity.label}
                          </span>
                        </div>
                        {plan.session_count ? (
                          <p className="text-[11px] text-app-muted-light mt-0.5">
                            {plan.session_count} حصة تدريبية
                          </p>
                        ) : null}
                      </div>
                      <div className="text-left">
                        <span className="font-semibold text-app-yellow text-sm block">
                          {formatMoney(regularPrice)}
                        </span>
                        {selectedOffer.offer_type === "single_choice" && savings > 0 && (
                          <span className="text-[11px] text-emerald-400 font-medium block">
                            توفير {formatMoney(savings)}
                          </span>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}
      </Drawer>

      {/* Delete Confirmation Dialog */}
      <ConfirmDialog
        open={canDelete && Boolean(deletingOffer)}
        onClose={() => {
          setDeletingOffer(null);
          setDeleteConfirmation("");
        }}
        onConfirm={handleConfirmDelete}
        title="تأكيد حذف العرض الترويجي"
        message={`هل أنت متأكد من رغبتك في حذف العرض الترويجي "${deletingOffer?.name || ""}"؟ لا يمكن التراجع عن هذا الإجراء.`}
        requiredConfirmation="delete"
        confirmationValue={deleteConfirmation}
        onConfirmationChange={setDeleteConfirmation}
        confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
        isLoading={isDeleting}
      />
    </div>
  );
}

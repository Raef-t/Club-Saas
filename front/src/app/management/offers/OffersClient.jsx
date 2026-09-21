"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import RowActions from "@/components/ui/RowActions";
import StatsGrid from "@/components/ui/StatsGrid";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { PlusIcon } from "@/components/icons/Icons";
import { useGetOffersQuery, useDeleteOfferMutation } from "@/lib/api/offersApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatMoney, formatLocalizedName } from "@/lib/utils";
import { usePermissions } from "@/lib/PermissionContext";
import { PAGE_SIZE_OPTIONS } from "@/lib/pagination";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import { OfferStatusBadge, OfferTypeBadge } from "./_components/OfferBadges";
import OfferDetailsDrawer from "./_components/OfferDetailsDrawer";
import OffersToolbar from "./_components/OffersToolbar";
import { getDurationInMonths } from "./_lib/durationHelpers";
import { filterOffers, getOffersCollection, getPlanCapacity } from "./_lib/offerPresentation";

const TABLE_GRID_COLUMNS = "48px minmax(180px,1.2fr) minmax(260px,2fr) 110px 120px 105px 110px";

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

  const offers = useMemo(() => getOffersCollection(data), [data]);

  // Client-side filtering
  const filteredOffers = useMemo(
    () => filterOffers(offers, { searchTerm, statusFilter }),
    [offers, searchTerm, statusFilter],
  );

  // Statistics for StatsGrid
  const stats = useMemo(() => {
    const total = offers.length;
    const available = offers.filter((o) => o.is_available).length;
    const inactive = offers.filter((o) => !o.is_active || !o.is_available).length;
    const totalActiveSubscribers = offers.reduce(
      (sum, o) => sum + (Number(o.active_subscribers_count) || 0),
      0,
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
        key: "rowNumber",
        label: "#",
        type: "rowNumber",
        align: "center",
        sortable: false,
      },
      {
        key: "name",
        label: "العرض الترويجي",
        align: "center",
        sortValue: (offer) => offer.name || "",
        render: (_, offer) => (
          <div className="min-w-0 text-center space-y-1">
            <p className="truncate text-sm font-medium text-app-text">{offer.name}</p>
            <div className="flex flex-wrap items-center justify-center gap-1.5">
              <OfferTypeBadge type={offer.offer_type} compact />
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
                <span className="font-medium text-app-yellow text-xs">{formatMoney(value)}</span>
              );
            }

            return (
              <div className="w-full flex flex-col justify-center divide-y divide-app-line/40">
                {plansList.map((plan) => (
                  <div key={plan.id} className="h-10 flex items-center justify-center">
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
          const durationMultiplier = getDurationInMonths(offer.duration_days) || 1;

          if (offer.offer_type === "single_choice") {
            const plansList = offer.plans || [];
            if (plansList.length === 0) {
              return <span className="text-xs text-app-muted-light">-</span>;
            }

            return (
              <div className="w-full flex flex-col justify-center divide-y divide-app-line/40">
                {plansList.map((plan) => {
                  const regularPrice =
                    (Number(plan.base_price ?? plan.price) || 0) * durationMultiplier;
                  const offerPrice = Number(offer.price) || 0;
                  const savings = regularPrice > offerPrice ? regularPrice - offerPrice : 0;

                  return (
                    <div key={plan.id} className="h-10 flex items-center justify-center">
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
            (offer.plans || []).reduce((s, p) => s + (Number(p.base_price) || 0), 0) *
              durationMultiplier;
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
                    <div key={plan.id} className="h-10 flex items-center justify-center">
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
                      canUpdate ? `/management/offers/create?mode=edit&id=${offer.id}` : undefined
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
                  <div key={plan.id} className="h-10 flex items-center justify-center gap-2">
                    <RowActions
                      disabled={isDeleting}
                      editHref={
                        canUpdate ? `/management/offers/create?mode=edit&id=${offer.id}` : undefined
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
                  canUpdate ? `/management/offers/create?mode=edit&id=${offer.id}` : undefined
                }
                onDelete={canDelete ? () => setDeletingOffer(offer) : undefined}
                className="gap-2"
              />
            </div>
          );
        },
      },
    ],
    [canDelete, canUpdate, isDeleting],
  );

  return (
    <div className="space-y-6" dir="rtl">
      {/* Top Header */}
      <PageHeader
        eyebrow="إدارة النادي"
        title="العروض الترويجية"
        subtitle="أنشئ عروضًا مرنة، راقب الإتاحة، وأدر أسعار الباقات من مكان واحد."
        action={
          canCreate ? (
            <Button
              href="/management/offers/create"
              icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
              className="h-12 min-w-52 rounded-xl px-5 font-semibold"
              style={{ color: "#000000" }}
            >
              إنشاء عرض ترويجي
            </Button>
          ) : null
        }
      />

      {/* Stats Grid */}
      <StatsGrid items={stats} variant="compact" />

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
          <OffersToolbar
            searchTerm={searchTerm}
            onSearchChange={setSearchTerm}
            statusFilter={statusFilter}
            onStatusFilterChange={setStatusFilter}
          />
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

      <OfferDetailsDrawer offer={selectedOffer} onClose={() => setSelectedOffer(null)} />

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

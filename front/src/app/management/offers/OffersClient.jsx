"use client";

import { useMemo, useState } from "react";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import SearchInput from "@/components/ui/SearchInput";
import Dropdown from "@/components/ui/Dropdown";
import SkeletonPage from "@/components/ui/Skeleton";
import { useGetOffersQuery } from "@/lib/api/offersApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatMoney, formatDate } from "@/lib/utils";
import OfferModal from "./OfferModal";
import SubscribeOfferModal from "./SubscribeOfferModal";
import DeleteOfferModal from "./DeleteOfferModal";
import { PencilIcon, TrashIcon, PlusIcon } from "@/components/icons/Icons";

const STATUS_FILTER_OPTIONS = [
  { value: "all", label: "جميع الحالات" },
  { value: "available", label: "المتاحة فقط" },
  { value: "inactive", label: "المعطلة أو المكتملة" },
];

export default function OffersClient() {
  const { selectedBranchId } = useManagementBranch();
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  // Modal states
  const [isOfferModalOpen, setIsOfferModalOpen] = useState(false);
  const [editingOffer, setEditingOffer] = useState(null);
  const [subscribingOffer, setSubscribingOffer] = useState(null);
  const [deletingOffer, setDeletingOffer] = useState(null);

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

  // Quick statistics
  const stats = useMemo(() => {
    const total = offers.length;
    const available = offers.filter((o) => o.is_available).length;
    const totalActiveSubscribers = offers.reduce(
      (sum, o) => sum + (o.active_subscribers_count || 0),
      0
    );
    return { total, available, totalActiveSubscribers };
  }, [offers]);

  function handleOpenCreate() {
    setEditingOffer(null);
    setIsOfferModalOpen(true);
  }

  function handleOpenEdit(offer) {
    setEditingOffer(offer);
    setIsOfferModalOpen(true);
  }

  return (
    <div className="space-y-6" dir="rtl">
      {/* Top Header */}
      <PageHeader
        eyebrow="إدارة النادي"
        title="العروض الترويجية والباقات"
        subtitle="إنشاء وإدارة باقات الأنشطة المتعددة بأسعار مخفضة وتسجيل اشتراكات اللاعبين فيها."
        action={
          <div className="flex flex-wrap gap-3">
            <Button
              tone="outline"
              className="h-10 px-4"
              onClick={refetch}
              disabled={isFetching}
            >
              {isFetching ? "جاري التحديث" : "تحديث البيانات"}
            </Button>
            <Button
              type="button"
              onClick={handleOpenCreate}
              icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
              style={{ color: "#000000" }}
            >
              إضافة عرض جديد
            </Button>
          </div>
        }
      />

      {/* Statistics Strip */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-4 text-right">
          <span className="text-xs text-app-muted-light block">إجمالي العروض</span>
          <span className="text-xl font-bold text-white mt-1 block">{stats.total}</span>
        </div>
        <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-4 text-right">
          <span className="text-xs text-app-muted-light block">العروض المتاحة حالياً</span>
          <span className="text-xl font-bold text-emerald-400 mt-1 block">
            {stats.available}
          </span>
        </div>
        <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-4 text-right">
          <span className="text-xs text-app-muted-light block">المشتركون النشطون بالعروض</span>
          <span className="text-xl font-bold text-app-yellow mt-1 block">
            {stats.totalActiveSubscribers} مشترك
          </span>
        </div>
      </div>

      {/* Search & Filter Toolbar */}
      <div className="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div className="w-full sm:w-80 md:w-96">
          <SearchInput
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="ابحث باسم العرض أو الخطة أو الوصف..."
            className="w-full"
          />
        </div>

        <div className="w-full sm:w-52">
          <Dropdown
            className="text-white"
            buttonClassName="bg-app-card-soft h-10 text-xs"
            value={statusFilter}
            onChange={setStatusFilter}
            options={STATUS_FILTER_OPTIONS}
          />
        </div>
      </div>

      {/* Grid Content */}
      {isLoading ? (
        <SkeletonPage blocks={[{ type: "grid", items: 6 }]} />
      ) : isError ? (
        <div className="rounded-xl border border-app-red/40 bg-app-red/10 p-6 text-center space-y-3">
          <p className="text-sm text-app-red">تعذر تحميل بيانات العروض الترويجية.</p>
          <Button tone="outline" className="h-9 px-4 text-xs" onClick={() => refetch()}>
            إعادة المحاولة
          </Button>
        </div>
      ) : filteredOffers.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-app-line bg-app-card-soft/40 p-12 text-center space-y-3">
          <span className="text-4xl block">🎁</span>
          <h3 className="text-base font-semibold text-white">لا توجد عروض ترويجية مطابقة</h3>
          <p className="text-xs text-app-muted-light max-w-md mx-auto">
            {searchTerm || statusFilter !== "all"
              ? "لم يتم العثور على أي باقات مطابقة لمعايير البحث الحالية."
              : "لم يتم إنشاء أي عروض ترويجية بعد. اضغط على زر 'إضافة عرض جديد' لتجميع الخطط بسعر مخفض."}
          </p>
          {!searchTerm && statusFilter === "all" && (
            <Button
              type="button"
              onClick={handleOpenCreate}
              icon={<PlusIcon className="size-4" style={{ color: "#000000" }} />}
              style={{ color: "#000000" }}
            >
              إضافة أول عرض
            </Button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {filteredOffers.map((offer) => {
            const isAvailable = Boolean(offer.is_available);
            const plans = offer.plans || [];

            return (
              <div
                key={offer.id}
                className="rounded-2xl border border-app-line bg-app-card-soft flex flex-col justify-between overflow-hidden shadow-lg transition hover:border-app-yellow/40 hover:shadow-2xl"
              >
                {/* Header & Details */}
                <div className="p-5 space-y-3">
                  <div className="flex items-start justify-between gap-2">
                    <span
                      className={`px-2.5 py-0.5 rounded-full text-[11px] font-semibold border shrink-0 ${
                        isAvailable
                          ? "bg-emerald-500/15 text-emerald-400 border-emerald-500/30"
                          : "bg-rose-500/15 text-rose-400 border-rose-500/30"
                      }`}
                    >
                      {isAvailable ? "متاح للاشتراك ✨" : "غير متاح / مكتمل السعة"}
                    </span>

                    {offer.branch && (
                      <span className="text-[11px] text-app-muted-light bg-black/30 px-2 py-0.5 rounded border border-app-line/50">
                        {offer.branch.name}
                      </span>
                    )}
                  </div>

                  <div>
                    <h3 className="text-base font-bold text-white leading-snug">{offer.name}</h3>
                    {offer.description && (
                      <p className="mt-1 text-xs text-app-muted-light line-clamp-2 leading-relaxed">
                        {offer.description}
                      </p>
                    )}
                  </div>

                  {/* Price Tag */}
                  <div className="flex items-baseline justify-between border-t border-app-line/60 pt-3">
                    <span className="text-xs text-app-muted-light">سعر الباقة الإجمالي:</span>
                    <span className="text-lg font-black text-app-yellow tracking-wide">
                      {formatMoney(offer.price)}
                    </span>
                  </div>

                  {/* Bundled Plans */}
                  <div className="space-y-1.5 pt-1">
                    <div className="flex justify-between text-[11px] text-app-muted-light">
                      <span>الخطط المشمولة:</span>
                      <span>{plans.length} خطة</span>
                    </div>

                    <div className="flex flex-wrap gap-1.5">
                      {plans.map((plan) => (
                        <span
                          key={plan.id}
                          className="rounded-lg bg-black/40 border border-app-line px-2 py-1 text-[11px] text-gray-200 flex items-center gap-1"
                        >
                          <span className="text-app-yellow text-[9px]">●</span>
                          <span>{plan.name}</span>
                          {plan.session_count && (
                            <span className="text-[10px] text-app-muted-light">
                              ({plan.session_count} حصة)
                            </span>
                          )}
                        </span>
                      ))}
                    </div>
                  </div>

                  {/* Metadata Info */}
                  <div className="rounded-xl bg-black/25 p-2.5 space-y-1.5 text-[11px] text-app-muted-light border border-app-line/50">
                    <div className="flex justify-between">
                      <span>المقاعد الشاغرة:</span>
                      <span className="font-semibold text-white">
                        {offer.available_slots !== null
                          ? `${offer.available_slots} مقعد`
                          : "سعة مفتوحة"}
                      </span>
                    </div>

                    <div className="flex justify-between">
                      <span>المشتركون النشطون:</span>
                      <span className="font-semibold text-cyan-400">
                        {offer.active_subscribers_count || 0} لاعب
                      </span>
                    </div>

                    {(offer.start_date || offer.end_date) && (
                      <div className="flex justify-between">
                        <span>الصلاحية:</span>
                        <span className="text-gray-300">
                          {offer.start_date ? formatDate(offer.start_date) : "الآن"} ←{" "}
                          {offer.end_date ? formatDate(offer.end_date) : "مستمر"}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Footer Actions */}
                <div className="p-4 pt-0 border-t border-app-line/60 flex items-center gap-2 mt-auto">
                  <Button
                    type="button"
                    tone="primary"
                    className="flex-1 h-9 text-xs font-bold"
                    disabled={!isAvailable}
                    onClick={() => setSubscribingOffer(offer)}
                  >
                    اشتراك لاعب 📝
                  </Button>

                  <button
                    type="button"
                    onClick={() => handleOpenEdit(offer)}
                    className="size-9 grid place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-yellow/60 hover:text-app-yellow"
                    title="تعديل العرض"
                  >
                    <PencilIcon className="size-3.5" />
                  </button>

                  <button
                    type="button"
                    onClick={() => setDeletingOffer(offer)}
                    className="size-9 grid place-items-center rounded-lg border border-app-line bg-app-card-soft text-app-muted-light transition hover:border-app-red/60 hover:text-app-red"
                    title="حذف العرض"
                  >
                    <TrashIcon className="size-3.5" />
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Modals */}
      <OfferModal
        open={isOfferModalOpen}
        onClose={() => setIsOfferModalOpen(false)}
        offer={editingOffer}
        initialBranchId={selectedBranchId}
      />

      <SubscribeOfferModal
        open={Boolean(subscribingOffer)}
        onClose={() => setSubscribingOffer(null)}
        offer={subscribingOffer}
      />

      <DeleteOfferModal
        open={Boolean(deletingOffer)}
        onClose={() => setDeletingOffer(null)}
        offer={deletingOffer}
      />
    </div>
  );
}

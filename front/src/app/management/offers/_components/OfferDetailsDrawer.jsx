import Drawer from "@/components/ui/Drawer";
import DetailItem from "@/components/ui/DetailItem";
import { formatLocalizedName, formatMoney } from "@/lib/utils";
import { OfferStatusBadge, OfferTypeBadge } from "./OfferBadges";
import { getPlanCapacity } from "../_lib/offerPresentation";
import { formatDurationDays, getDurationInMonths } from "../_lib/durationHelpers";

export default function OfferDetailsDrawer({ offer, onClose }) {
  return (
    <Drawer
      open={Boolean(offer)}
      onClose={onClose}
      title="تفاصيل العرض الترويجي"
      subtitle={offer?.name}
    >
      {offer && (
        <div className="space-y-6">
          <div className="rounded-2xl border border-app-line bg-app-card-soft/70 p-4 text-right">
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <h3 className="truncate text-lg font-bold text-app-text">{offer.name}</h3>
                {offer.description && (
                  <p className="mt-1 text-xs leading-relaxed text-app-muted-light">
                    {offer.description}
                  </p>
                )}
                <div className="mt-3">
                  <OfferTypeBadge type={offer.offer_type} />
                </div>
              </div>
              <OfferStatusBadge offer={offer} />
            </div>
          </div>

          <section className="grid gap-3 sm:grid-cols-2">
            <DetailItem
              label={
                offer.offer_type === "single_choice" ? "سعر الفعالية المخفض" : "سعر الباقة الإجمالي"
              }
              value={formatMoney(offer.price)}
              tone="yellow"
            />
            {(offer.duration_days || offer.duration_formatted) && (
              <DetailItem
                label="مدة اشتراك اللاعب"
                value={
                  offer.duration_formatted ||
                  `${formatDurationDays(offer.duration_days)} (${offer.duration_days} يوم)`
                }
                tone="cyan"
              />
            )}
            <DetailItem
              label="المشتركون النشطون"
              value={`${offer.active_subscribers_count || 0} مشترك`}
            />
            <DetailItem
              label="مدة العرض"
              value={
                offer.end_date
                  ? `من ${offer.start_date || "الآن"} إلى ${offer.end_date}`
                  : "غير محدد بتاريخ"
              }
            />
            <DetailItem
              label="حالة التفعيل"
              value={offer.is_active ? "مفعل" : "معطل"}
              tone={offer.is_active ? "green" : "red"}
            />
          </section>

          <section className="space-y-3">
            <div>
              <p className="text-sm font-semibold text-app-text">الفعاليات المشمولة</p>
              <p className="mt-1 text-xs text-app-muted-light">
                {offer.offer_type === "single_choice"
                  ? "يختار المشترك فعالية واحدة من القائمة."
                  : "يُسجل المشترك في جميع فعاليات الباقة."}
              </p>
            </div>
            <div className="space-y-2">
              {(offer.plans || []).map((plan) => {
                const capacity = getPlanCapacity(plan);
                const durationMultiplier = getDurationInMonths(offer.duration_days) || 1;
                const monthlyPrice = Number(plan.base_price ?? plan.price) || 0;
                const regularPrice = monthlyPrice * durationMultiplier;
                const savings = Math.max(0, regularPrice - (Number(offer.price) || 0));

                return (
                  <article
                    key={plan.id}
                    className="flex items-center justify-between gap-3 rounded-xl border border-app-line bg-app-card-soft/50 p-3"
                  >
                    <div className="min-w-0 text-right">
                      <p className="truncate text-sm font-medium text-app-text">
                        {formatLocalizedName(plan.name) || plan.name}
                      </p>
                      <p
                        className={`mt-1 text-[11px] font-medium ${
                          capacity.isFull
                            ? "text-amber-400"
                            : capacity.isUnlimited
                              ? "text-app-muted-light"
                              : "text-emerald-400"
                        }`}
                      >
                        {capacity.label}
                      </p>
                    </div>
                    <div className="shrink-0 text-left">
                      <span className="block text-sm font-semibold text-app-yellow">
                        {formatMoney(regularPrice)}
                      </span>
                      {durationMultiplier !== 1 && (
                        <span className="block text-[10px] text-app-muted-light">
                          {formatMoney(monthlyPrice)} شهرياً
                        </span>
                      )}
                      {offer.offer_type === "single_choice" && savings > 0 && (
                        <span className="block text-[11px] font-medium text-emerald-400">
                          توفير {formatMoney(savings)}
                        </span>
                      )}
                    </div>
                  </article>
                );
              })}
            </div>
          </section>
        </div>
      )}
    </Drawer>
  );
}

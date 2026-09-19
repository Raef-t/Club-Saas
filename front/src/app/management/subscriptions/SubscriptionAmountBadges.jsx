"use client";

import AppTooltip from "@/components/ui/AppTooltip";
import {
  formatSubscriptionMoney,
  getSubscriptionDiscountSummary,
  getSubscriptionOriginalAmounts,
  isPrivateSubscriptionPlan,
} from "./subscriptionUtils";

function AmountBadge({ label, value, tone }) {
  const toneClass =
    tone === "paid"
      ? "border-app-green/25 bg-app-green/10 text-app-green"
      : tone === "coach"
        ? "border-blue-400/25 bg-blue-400/10 text-blue-200"
        : tone === "branch"
          ? "border-app-yellow/25 bg-app-yellow/10 text-app-yellow"
          : "border-blue-400/25 bg-blue-400/10 text-blue-200";

  return (
    <span
      className={`inline-flex max-w-full items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] ${toneClass}`}
    >
      {label && <span className="shrink-0 opacity-75">{label}:</span>}
      <span className="truncate font-medium" dir="ltr">
        {formatSubscriptionMoney(value)}
      </span>
    </span>
  );
}

/** Displays the subscription net price and paid amount details with a styled tooltip. */
export default function SubscriptionAmountBadges({ subscription }) {
  const discount = getSubscriptionDiscountSummary(subscription);
  const isPrivatePlan = isPrivateSubscriptionPlan(subscription?.plan || subscription);
  const { coachOriginal, branchOriginal } = getSubscriptionOriginalAmounts(
    subscription?.plan,
    subscription?.months_count,
  );

  const tooltipContent = (
    <div className="min-w-[175px] space-y-1.5 text-xs" dir="rtl">
      <div className="flex items-center gap-1.5 border-b border-app-line/60 pb-1.5 text-[11px] font-semibold text-app-text">
        <span className="size-1.5 rounded-full bg-blue-400" />
        <span>تفاصيل المبلغ</span>
      </div>
      <div className="flex items-center justify-between gap-4">
        <span className="text-app-muted-light">الصافي:</span>
        <span className="font-semibold text-blue-300" dir="ltr">
          {formatSubscriptionMoney(discount.finalPrice)}
        </span>
      </div>
      <div className="flex items-center justify-between gap-4">
        <span className="text-app-muted-light">المدفوع:</span>
        <span className="font-semibold text-app-green" dir="ltr">
          {formatSubscriptionMoney(subscription?.paid_amount)}
        </span>
      </div>
      {isPrivatePlan && (
        <>
          <div className="flex items-center justify-between gap-4 border-t border-app-line/40 pt-1.5">
            <span className="text-app-muted-light">سعر الكوتش:</span>
            <span className="font-semibold text-blue-300" dir="ltr">
              {formatSubscriptionMoney(coachOriginal)}
            </span>
          </div>
          <div className="flex items-center justify-between gap-4">
            <span className="text-app-muted-light">سعر النادي:</span>
            <span className="font-semibold text-app-yellow" dir="ltr">
              {formatSubscriptionMoney(branchOriginal)}
            </span>
          </div>
        </>
      )}
      {discount.isDiscount && (
        <div className="flex items-center justify-between gap-4 border-t border-app-line/40 pt-1.5 text-[11px] text-app-yellow">
          <span>حسم {discount.discountPercentage}%</span>
          {subscription?.discount_reason && (
            <span className="text-[10px] text-app-muted-light">({subscription.discount_reason})</span>
          )}
        </div>
      )}
    </div>
  );

  return (
    <div className="flex min-w-0 flex-col items-center gap-1.5">
      {discount.isDiscount && (
        <div className="flex items-center gap-1.5 text-[10px]">
          <span className="text-app-muted line-through">
            {formatSubscriptionMoney(discount.originalTotal)}
          </span>
          <span
            className="rounded-full border border-app-yellow/25 bg-app-yellow/10 px-2 py-0.5 font-medium text-app-yellow"
            title={subscription?.discount_reason || undefined}
          >
            حسم {discount.discountPercentage}%
          </span>
        </div>
      )}

      <AppTooltip content={tooltipContent}>
        <AmountBadge value={discount.finalPrice} tone="net" />
      </AppTooltip>
    </div>
  );
}

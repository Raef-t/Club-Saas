"use client";

import AppTooltip from "@/components/ui/AppTooltip";
import {
  formatSubscriptionMoney,
  getSubscriptionDiscountSummary,
  getSubscriptionOriginalAmounts,
  isPrivateSubscriptionPlan,
  parseSubscriptionAmount,
} from "./subscriptionUtils";

function AmountBadge({ label, value, tone }) {
  const toneClass =
    tone === "coach"
      ? "border-blue-400/25 bg-blue-400/10 text-blue-200"
      : tone === "branch"
        ? "border-app-yellow/25 bg-app-yellow/10 text-app-yellow"
        : "border-app-green/25 bg-app-green/10 text-app-green";

  return (
    <span
      className={`inline-flex max-w-full items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] ${toneClass}`}
      dir="rtl"
    >
      {label && <span className="shrink-0 opacity-75">{label}:</span>}
      <span className="truncate font-medium">
        <bdi dir="ltr">{formatSubscriptionMoney(value)}</bdi>
      </span>
    </span>
  );
}

/** Displays the subscription amounts directly in the table with custom tooltip. */
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
        <span className="size-1.5 rounded-full bg-app-green" />
        <span>تفاصيل المبلغ</span>
      </div>
      <div className="flex items-center justify-between gap-4">
        <span className="text-app-muted-light">المبلغ:</span>
        <span className="font-semibold text-app-green">
          <bdi dir="ltr">{formatSubscriptionMoney(discount.finalPrice)}</bdi>
        </span>
      </div>
      <div className="flex items-center justify-between gap-4">
        <span className="text-app-muted-light">المدفوع:</span>
        <span className="font-semibold text-app-green">
          <bdi dir="ltr">{formatSubscriptionMoney(subscription?.paid_amount)}</bdi>
        </span>
      </div>
      {isPrivatePlan && (
        <>
          <div className="flex items-center justify-between gap-4 border-t border-app-line/40 pt-1.5">
            <span className="text-app-muted-light">الكوتش:</span>
            <span className="font-semibold text-blue-300">
              <bdi dir="ltr">{formatSubscriptionMoney(coachOriginal)}</bdi>
            </span>
          </div>
          <div className="flex items-center justify-between gap-4">
            <span className="text-app-muted-light">النادي:</span>
            <span className="font-semibold text-app-yellow">
              <bdi dir="ltr">{formatSubscriptionMoney(branchOriginal)}</bdi>
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

  const isPartiallyPaid =
    subscription?.paid_amount !== undefined &&
    subscription?.paid_amount !== null &&
    Number(subscription.paid_amount) !== Number(discount.finalPrice);

  return (
    <div className="flex min-w-0 flex-col items-center gap-1.5">
      {discount.isDiscount && (
        <div className="flex items-center gap-1.5 text-[10px]">
          <span className="text-app-muted line-through">
            <bdi dir="ltr">{formatSubscriptionMoney(discount.originalTotal)}</bdi>
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
        <div className="flex min-w-0 flex-col items-center gap-1">
          <AmountBadge label="المبلغ" value={discount.finalPrice} tone="net" />
          {isPrivatePlan && (
            <div
              className="flex min-w-0 flex-col items-center gap-1"
              aria-label="تفصيل سعر الكوتش والنادي"
            >
              <AmountBadge label="الكوتش" value={coachOriginal} tone="coach" />
              <AmountBadge label="النادي" value={branchOriginal} tone="branch" />
            </div>
          )}
          {isPartiallyPaid && (
            <AmountBadge label="المدفوع" value={subscription.paid_amount} tone="paid" />
          )}
        </div>
      </AppTooltip>
    </div>
  );
}

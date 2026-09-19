"use client";

import AppTooltip from "@/components/ui/AppTooltip";
import { getSubscriptionReceiptNumbers, isPrivateSubscriptionPlan } from "./subscriptionUtils";

function ReceiptBadge({ label, value, tone }) {
  const toneClass =
    tone === "coach"
      ? "border-blue-400/25 bg-blue-400/10 text-blue-200"
      : tone === "branch"
        ? "border-app-yellow/25 bg-app-yellow/10 text-app-yellow"
        : "border-app-line bg-app-card-soft text-app-text";

  return (
    <span
      className={`inline-flex max-w-full items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] ${toneClass}`}
    >
      {label && <span className="shrink-0 opacity-75">{label}:</span>}
      <span className="truncate font-medium" dir="ltr">
        {value}
      </span>
    </span>
  );
}

/** Displays the receipt numbers with labels directly in the table with custom tooltip. */
export default function SubscriptionReceiptBadges({ subscription, className = "" }) {
  const { receiptNumber, coachReceiptNumber, branchReceiptNumber } =
    getSubscriptionReceiptNumbers(subscription);
  const hasPrivateReceipts =
    isPrivateSubscriptionPlan(subscription?.plan || subscription) &&
    Boolean(coachReceiptNumber || branchReceiptNumber);

  if (!hasPrivateReceipts && !receiptNumber) {
    return <span className="text-app-muted-light">-</span>;
  }

  const tooltipContent = (
    <div className="min-w-[155px] space-y-1.5 text-xs" dir="rtl">
      <div className="flex items-center gap-1.5 border-b border-app-line/60 pb-1.5 text-[11px] font-semibold text-app-text">
        <span className="size-1.5 rounded-full bg-app-yellow" />
        <span>تفاصيل الإيصالات</span>
      </div>
      {hasPrivateReceipts ? (
        <>
          {coachReceiptNumber && (
            <div className="flex items-center justify-between gap-4">
              <span className="text-app-muted-light">إيصال الكوتش:</span>
              <span className="font-semibold text-blue-300" dir="ltr">
                {coachReceiptNumber}
              </span>
            </div>
          )}
          {branchReceiptNumber && (
            <div className="flex items-center justify-between gap-4">
              <span className="text-app-muted-light">إيصال النادي:</span>
              <span className="font-semibold text-app-yellow" dir="ltr">
                {branchReceiptNumber}
              </span>
            </div>
          )}
        </>
      ) : (
        <div className="flex items-center justify-between gap-4">
          <span className="text-app-muted-light">رقم الإيصال:</span>
          <span className="font-semibold text-app-text" dir="ltr">
            {receiptNumber}
          </span>
        </div>
      )}
    </div>
  );

  return (
    <div className={`flex min-w-0 flex-col items-center gap-1.5 ${className}`}>
      <AppTooltip content={tooltipContent}>
        <div className="flex min-w-0 flex-col items-center gap-1">
          {hasPrivateReceipts ? (
            <>
              {coachReceiptNumber && (
                <ReceiptBadge label="إيصال الكوتش" value={coachReceiptNumber} tone="coach" />
              )}
              {branchReceiptNumber && (
                <ReceiptBadge label="إيصال النادي" value={branchReceiptNumber} tone="branch" />
              )}
            </>
          ) : (
            <ReceiptBadge label="الإيصال" value={receiptNumber} />
          )}
        </div>
      </AppTooltip>
    </div>
  );
}

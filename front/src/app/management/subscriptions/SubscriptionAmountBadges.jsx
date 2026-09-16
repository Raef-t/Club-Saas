import { formatSubscriptionMoney, getSubscriptionDiscountSummary } from "./subscriptionUtils";

function AmountBadge({ label, value, tone }) {
  const toneClass =
    tone === "paid"
      ? "border-app-green/25 bg-app-green/10 text-app-green"
      : "border-blue-400/25 bg-blue-400/10 text-blue-200";

  return (
    <span
      className={`inline-flex max-w-full items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] ${toneClass}`}
      title={`${label}: ${formatSubscriptionMoney(value)}`}
    >
      <span className="shrink-0 opacity-75">{label}:</span>
      <span className="truncate font-medium" dir="ltr">
        {formatSubscriptionMoney(value)}
      </span>
    </span>
  );
}

/** Displays the subscription net price and paid amount as compact badges. */
export default function SubscriptionAmountBadges({ subscription }) {
  const discount = getSubscriptionDiscountSummary(subscription);

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

      <AmountBadge label="الصافي" value={discount.finalPrice} tone="net" />
      <AmountBadge label="المدفوع" value={subscription?.paid_amount} tone="paid" />
    </div>
  );
}

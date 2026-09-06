import { getSubscriptionReceiptNumbers } from "./subscriptionUtils";

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
      title={`${label}: ${value}`}
    >
      <span className="shrink-0 opacity-75">{label}:</span>
      <span className="truncate font-medium" dir="ltr">
        {value}
      </span>
    </span>
  );
}

/** Displays the general receipt or the two receipts associated with a private plan. */
export default function SubscriptionReceiptBadges({ subscription, className = "" }) {
  const { receiptNumber, coachReceiptNumber, branchReceiptNumber } =
    getSubscriptionReceiptNumbers(subscription);
  const hasPrivateReceipts = Boolean(coachReceiptNumber || branchReceiptNumber);

  if (!hasPrivateReceipts && !receiptNumber) {
    return <span className="text-app-muted-light">-</span>;
  }

  return (
    <div className={`flex min-w-0 flex-col items-center gap-1.5 ${className}`}>
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
  );
}

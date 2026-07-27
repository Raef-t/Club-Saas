import { SUBSCRIPTION_STATUS_CLASSES, SUBSCRIPTION_STATUS_LABELS } from "./subscriptionConstants";

/**
 * Displays a localized subscription status with its matching visual tone.
 */
export default function SubscriptionStatusBadge({ status }) {
  const statusClass = SUBSCRIPTION_STATUS_CLASSES[status] || "bg-white/10 text-app-muted-light";

  return (
    <span
      className={`inline-flex min-w-20 justify-center rounded-md px-3 py-1 text-xs font-medium ${statusClass}`}
    >
      {SUBSCRIPTION_STATUS_LABELS[status] || status || "-"}
    </span>
  );
}

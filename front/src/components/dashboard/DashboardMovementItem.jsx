import { ArrowUpIcon } from "@/components/icons/Icons";

/**
 * Renders a compact incoming or outgoing financial movement.
 */
export default function DashboardMovementItem({
  title,
  description,
  amount,
  meta,
  direction = "in",
}) {
  const isIncoming = direction === "in";

  return (
    <div className="flex h-12 items-center gap-3 rounded-lg bg-app-card-soft px-3">
      <div
        className={`grid size-9 shrink-0 place-items-center rounded-md ${
          isIncoming ? "bg-app-green/20 text-app-green" : "bg-app-red/20 text-app-red"
        }`}
      >
        <ArrowUpIcon className={`size-5 ${isIncoming ? "rotate-[225deg]" : "rotate-45"}`} />
      </div>
      <div className="min-w-0 flex-1 text-end">
        <h4 className="truncate text-sm font-medium text-app-text">{title}</h4>
        <p className="truncate text-xs text-app-muted">{description}</p>
      </div>
      <div className="text-center text-xs text-app-muted">
        <strong
          className={`block text-sm font-medium ${isIncoming ? "text-app-green" : "text-app-red"}`}
        >
          {amount}
        </strong>
        {meta}
      </div>
    </div>
  );
}

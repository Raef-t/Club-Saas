import { SealCheckIcon } from "@/components/icons/Icons";

/**
 * Renders one upcoming payment summary.
 */
export function UpcomingPaymentItem({ item }) {
  return (
    <div className="flex h-12 items-center justify-between rounded-lg bg-app-card-soft px-3">
      <div className="text-start text-sm font-medium text-app-yellow">{item.amount}</div>
      <div className="text-end">
        <h4 className="text-sm font-medium text-app-text">{item.title}</h4>
        <p className="text-xs text-app-muted">{item.date}</p>
      </div>
    </div>
  );
}

/**
 * Renders trainer salary metrics and their calculation status.
 */
export function SalarySummaryPanel({ items }) {
  return (
    <>
      <div className="flex justify-center gap-5 px-5 pt-8" dir="ltr">
        {items.map((item) => (
          <div
            key={item.label}
            className="grid h-24 w-24 place-items-center rounded-lg bg-app-card-soft text-center"
          >
            <span className="text-sm text-[#b3b1b1]">{item.label}</span>
            <strong
              className={`text-xl font-medium ${
                item.tone === "yellow" ? "text-app-yellow" : "text-white"
              }`}
            >
              {item.value}
            </strong>
          </div>
        ))}
      </div>
      <div className="mt-10 flex items-center justify-center gap-4 text-xs text-app-muted">
        <SealCheckIcon className="size-6 text-app-green" />
        تم احتساب الرواتب تلقائياً وفق نظام النسب
      </div>
    </>
  );
}

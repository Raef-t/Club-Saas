import Button from "@/components/ui/Button";
import { CheckCircleIcon, GridIcon, PrintIcon, RefreshIcon } from "@/components/icons/Icons";

/**
 * Shared visual shell for every detailed report page.
 * Keeps report identity, scope, freshness and primary actions in one predictable place.
 */
export default function ReportPageShell({
  title,
  description,
  branchName,
  isRefreshing = false,
  onRefresh,
  onPrint,
  printLabel = "طباعة التقرير",
  children,
}) {
  return (
    <div className="space-y-6" dir="rtl">
      <header className="card-shell relative isolate overflow-hidden rounded-3xl border-app-line/80">
        <div
          className="pointer-events-none absolute inset-y-0 right-0 -z-10 w-2/3 bg-[radial-gradient(circle_at_top_right,rgba(242,220,46,0.13),transparent_60%)]"
          aria-hidden="true"
        />

        <div className="flex flex-col gap-6 px-5 py-5 sm:px-6 sm:py-6 xl:flex-row xl:items-end xl:justify-between">
          <div className="min-w-0">
            <div className="mb-4 flex flex-wrap items-center gap-2">
              <span className="inline-flex items-center gap-2 rounded-full border border-app-yellow/25 bg-app-yellow-soft px-3 py-1.5 text-xs font-medium text-app-yellow">
                <GridIcon className="size-3.5" />
                نظام التقارير
              </span>
              <span className="inline-flex items-center gap-1.5 rounded-full border border-app-line bg-app-card-soft/80 px-3 py-1.5 text-xs text-app-muted-light">
                <CheckCircleIcon className="size-3.5 text-app-green" />
                {isRefreshing ? "جاري تحديث البيانات" : "بيانات مباشرة"}
              </span>
            </div>

            <h1 className="max-w-3xl text-xl font-semibold leading-tight text-app-text sm:text-2xl">
              {title}
            </h1>
            <p className="mt-2 max-w-3xl text-sm leading-6 text-app-muted-light">{description}</p>

            <div className="mt-5 inline-flex max-w-full items-center gap-2 rounded-xl border border-app-line bg-app-card-soft/70 px-3.5 py-2 text-xs">
              <span className="shrink-0 text-app-muted">نطاق التقرير</span>
              <span className="h-4 w-px bg-app-line" aria-hidden="true" />
              <strong className="truncate font-medium text-app-text">
                {branchName || "الفرع الحالي"}
              </strong>
            </div>
          </div>

          <div className="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:flex-wrap xl:justify-end">
            {onRefresh && (
              <Button
                type="button"
                tone="outline"
                icon={<RefreshIcon className="size-4" />}
                loading={isRefreshing}
                loadingLabel="جاري التحديث"
                onClick={onRefresh}
                className="w-full sm:w-auto"
              >
                تحديث البيانات
              </Button>
            )}
            {onPrint && (
              <Button
                type="button"
                icon={<PrintIcon className="size-4" />}
                onClick={onPrint}
                className="w-full sm:w-auto"
              >
                {printLabel}
              </Button>
            )}
          </div>
        </div>
      </header>

      {children}
    </div>
  );
}

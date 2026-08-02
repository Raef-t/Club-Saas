import Button from "@/components/ui/Button";
import {
  ClockIcon,
  FolderPlusIcon,
  GiftIcon,
  GridIcon,
  PrintIcon,
  SealCheckIcon,
  TagIcon,
} from "@/components/icons/Icons";

const VISIBLE_ROW_LIMIT = 100;
const reportIcons = {
  hall: GridIcon,
  expired: ClockIcon,
  activities: GiftIcon,
  subscriptions: TagIcon,
  members: FolderPlusIcon,
  coaches: SealCheckIcon,
};

/**
 * Renders every report type as a selectable visual card.
 */
export function ReportCards({ reports, selectedReportId, onSelect }) {
  return (
    <div
      className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
      role="tablist"
      aria-label="أنواع التقارير"
    >
      {reports.map((report) => {
        const selected = report.id === selectedReportId;
        const Icon = reportIcons[report.id] || GridIcon;

        return (
          <button
            key={report.id}
            type="button"
            role="tab"
            aria-selected={selected}
            onClick={() => onSelect(report.id)}
            className={`group min-h-36 rounded-2xl border p-4 text-right transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-app-yellow/60 ${
              selected
                ? "border-app-yellow bg-[linear-gradient(135deg,rgba(242,220,46,0.16),rgba(242,220,46,0.04))] shadow-[0_12px_30px_rgba(242,220,46,0.08)]"
                : "border-app-line bg-app-card-soft hover:-translate-y-0.5 hover:border-app-yellow/50 hover:bg-app-card-hover"
            }`}
          >
            <span className="flex items-start justify-between gap-3">
              <span
                className={`grid size-11 shrink-0 place-items-center rounded-xl transition ${
                  selected
                    ? "bg-app-yellow text-app-bg"
                    : "bg-app-yellow/10 text-app-yellow group-hover:bg-app-yellow/20"
                }`}
              >
                <Icon className="size-5" />
              </span>
              <span
                className={`rounded-full px-2.5 py-1 text-[11px] ${
                  selected
                    ? "bg-app-yellow/15 text-app-yellow"
                    : "bg-app-panel-soft text-app-muted-light"
                }`}
              >
                {report.rows.length.toLocaleString("ar")} سجل
              </span>
            </span>
            <span
              className={`mt-4 block text-base font-medium ${
                selected ? "text-app-yellow" : "text-app-text"
              }`}
            >
              {report.title}
            </span>
            <span className="mt-1.5 block text-xs leading-5 text-app-muted-light">
              {report.description}
            </span>
          </button>
        );
      })}
    </div>
  );
}

/**
 * Displays one metric belonging to the selected report.
 */
function ReportMetric({ metric }) {
  return (
    <div className="rounded-xl border border-app-line bg-app-card-soft px-4 py-3 text-right">
      <p className="text-xs text-app-muted-light">{metric.label}</p>
      <strong className="mt-1 block text-xl font-medium text-app-yellow">
        {Number.isFinite(metric.value) ? metric.value.toLocaleString("ar") : metric.value}
      </strong>
    </div>
  );
}

/**
 * Renders a fast preview of the selected report and its print action.
 */
export function ReportPanel({ report, branchName, onPrint }) {
  const visibleRows = report.rows.slice(0, VISIBLE_ROW_LIMIT);
  const hiddenRowCount = report.rows.length - visibleRows.length;

  return (
    <section className="card-shell overflow-hidden rounded-2xl" aria-labelledby="report-title">
      <div className="flex flex-col gap-4 border-b border-app-line px-5 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div className="text-right">
          <p className="text-xs font-medium text-app-yellow">الفرع: {branchName}</p>
          <h2 id="report-title" className="mt-2 text-xl font-medium text-app-text">
            {report.title}
          </h2>
          <p className="mt-2 max-w-3xl text-sm text-app-muted-light">{report.description}</p>
        </div>
        <Button
          type="button"
          tone="outline"
          icon={<PrintIcon className="size-4" />}
          onClick={onPrint}
          className="shrink-0"
        >
          طباعة هذا التقرير
        </Button>
      </div>

      <div className="grid grid-cols-[repeat(auto-fit,minmax(min(100%,150px),1fr))] gap-3 px-5 py-4">
        {report.metrics.map((metric) => (
          <ReportMetric key={metric.label} metric={metric} />
        ))}
      </div>

      <div className="overflow-x-auto px-5 pb-5">
        <table className="w-full min-w-[720px] border-separate border-spacing-y-2 text-right text-sm">
          <thead className="text-xs text-app-muted-light">
            <tr>
              {report.columns.map((column) => (
                <th key={column.key} className="px-4 pb-1 font-medium">
                  {column.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {visibleRows.map((row, rowIndex) => (
              <tr key={`${report.id}-${rowIndex}`} className="bg-app-card-soft">
                {report.columns.map((column, columnIndex) => (
                  <td
                    key={column.key}
                    className={`px-4 py-3 text-app-text ${
                      columnIndex === 0
                        ? "rounded-s-xl"
                        : columnIndex === report.columns.length - 1
                          ? "rounded-e-xl"
                          : ""
                    }`}
                  >
                    {row[column.key] ?? "-"}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>

        {!visibleRows.length && (
          <div className="grid min-h-40 place-items-center rounded-xl border border-dashed border-app-line px-5 text-center text-sm text-app-muted">
            {report.emptyMessage}
          </div>
        )}

        {hiddenRowCount > 0 && (
          <p className="mt-3 text-center text-xs text-app-muted-light">
            تعرض المعاينة أول {VISIBLE_ROW_LIMIT.toLocaleString("ar")} سجل لتحسين الأداء، وتحتوي
            الطباعة على جميع السجلات وعددها {report.rows.length.toLocaleString("ar")}.
          </p>
        )}
      </div>
    </section>
  );
}

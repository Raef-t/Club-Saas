"use client";

import Button from "@/components/ui/Button";
import {
  DownloadIcon,
  FilterIcon,
  PlusIcon,
  SearchIcon,
} from "@/components/icons/Icons";
import { Field } from "@/components/forms/Field";
import SkeletonPage from "@/components/ui/Skeleton";

const statusClass = {
  مدفوع: "bg-[rgba(19,172,73,0.18)] text-app-green",
  معلق: "bg-[rgba(252,153,3,0.18)] text-[#d99300]",
  متأخر: "bg-[rgba(228,0,0,0.18)] text-app-red",
  "قيد المراجعة": "bg-[rgba(252,205,3,0.18)] text-app-yellow",
};

function getVisiblePages(page, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  if (page <= 3) return [1, 2, 3, 4, "...", total - 1, total];
  if (page >= total - 2)
    return [1, 2, "...", total - 3, total - 2, total - 1, total];
  return [1, "...", page - 1, page, page + 1, "...", total];
}

function getAlignClass(align = "end") {
  if (align === "center") return "justify-center text-center";
  if (align === "start") return "justify-start text-start";
  return "justify-end text-end";
}

function CellValue({ column, row, value }) {
  if (column.render) return column.render(value, row, column);

  if (column.type === "status") {
    return (
      <span
        className={`inline-flex rounded-md px-3 py-1 text-xs font-medium ${
          statusClass[value] || "bg-white/10 text-app-muted-light"
        }`}
      >
        {value}
      </span>
    );
  }

  if (column.type === "money") {
    return <span className="font-medium text-app-yellow">{value}</span>;
  }

  if (column.type === "in") {
    return <span className="font-medium text-app-green">{value}</span>;
  }

  if (column.type === "out") {
    return <span className="font-medium text-app-red">{value}</span>;
  }

  if (column.type === "warning") {
    return <span className="font-medium text-[#d99300]">{value}</span>;
  }

  return <span className="min-w-0 truncate">{value}</span>;
}

function Toolbar({
  title,
  subtitle,
  addLabel = "إضافة",
  showAdd = true,
  showSearch = true,
  showFilter = true,
  showExport = true,
  onAdd,
  searchValue,
  onSearchChange,
  toolbarActions,
  toolbarMeta,
}) {
  return (
    <div className="flex flex-col gap-4 border-b border-app-line px-4 py-4 lg:flex-row lg:items-start lg:justify-between">
      <div className="min-w-0 text-start">
        <h2 className="text-base font-medium text-app-text">{title}</h2>
        {subtitle && <p className="mt-1 text-xs text-app-muted">{subtitle}</p>}

        <div className="mt-4 flex flex-wrap items-center justify-end gap-3">
          {showAdd && (
            <Button
              onClick={onAdd}
              icon={<PlusIcon className="size-4" />}
              className="h-9 px-3 text-xs"
            >
              {addLabel}
            </Button>
          )}

          {toolbarActions}

          {showSearch && (
            <Field
              type="search"
              placeholder="البحث"
              icon={SearchIcon}
              className="min-w-48"
              dir="rtl"
              required={false}
              value={searchValue !== undefined ? searchValue : ""}
              onChange={(e) => onSearchChange?.(e.target.value)}
            />
          )}

          {showFilter && (
            <Button
              tone="ghost"
              icon={<FilterIcon className="size-4" />}
              className="h-9 px-3 text-xs"
            >
              تصفية
            </Button>
          )}

          {showExport && (
            <Button
              tone="ghost"
              icon={<DownloadIcon className="size-4" />}
              className="h-9 px-3 text-xs"
            >
              تصدير
            </Button>
          )}
        </div>
      </div>

      {toolbarMeta && (
        <div className="shrink-0 text-start lg:pt-1">{toolbarMeta}</div>
      )}
    </div>
  );
}

export default function DataTable({
  title,
  subtitle,
  columns,
  rows,
  addLabel,
  showToolbar = true,
  showAdd = true,
  showSearch = true,
  showFilter = true,
  showExport = true,
  onAdd,
  searchValue,
  onSearchChange,
  toolbarActions,
  toolbarMeta,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  onRowClick,
  getRowKey,
  tableColumns,
  minWidth = "780px",
  isLoading = false,
  loadingRows = 5,
  emptyMessage = "لا توجد بيانات",
  headerClassName = "",
  rowClassName = "",
  cellClassName = "",
}) {
  const resolvedTableColumns =
    tableColumns || columns.map((column) => column.width || "1fr").join(" ");
  const visiblePages =
    totalPages > 0 ? getVisiblePages(currentPage, totalPages) : [];

  return (
    <section className="app-card overflow-hidden rounded-2xl">
      {showToolbar && (
        <Toolbar
          title={title}
          subtitle={subtitle}
          addLabel={addLabel}
          showAdd={showAdd}
          showSearch={showSearch}
          showFilter={showFilter}
          showExport={showExport}
          onAdd={onAdd}
          searchValue={searchValue}
          onSearchChange={onSearchChange}
          toolbarActions={toolbarActions}
          toolbarMeta={toolbarMeta}
        />
      )}

      <div className="overflow-x-auto scrollbar-thin px-4 pb-4">
        <div style={{ minWidth }}>
          <div
            className={`grid border-b border-app-line px-3 py-3 text-xs text-app-muted-light ${headerClassName}`}
            style={{ gridTemplateColumns: resolvedTableColumns }}
          >
            {columns.map((column) => (
              <div
                key={column.key}
                className={`flex min-w-0 items-center ${getAlignClass(column.align)}`}
              >
                {column.label}
              </div>
            ))}
          </div>

          {isLoading ? (
            <SkeletonPage
              className="space-y-0"
              blocks={[
                { type: "list", count: loadingRows, itemClassName: "h-16" },
              ]}
            />
          ) : rows.length === 0 ? (
            <div className="mt-3 rounded-xl border border-app-line bg-app-card-soft/60 p-8 text-center text-sm text-app-muted-light">
              {emptyMessage}
            </div>
          ) : (
            <div className="space-y-3 pt-3">
              {rows.map((row, index) => {
                const key =
                  getRowKey?.(row, index) ?? `${row.id || index}-${index}`;

                return (
                  <div
                    key={key}
                    role={onRowClick ? "button" : undefined}
                    tabIndex={onRowClick ? 0 : undefined}
                    className={`grid w-full items-center rounded-lg bg-[rgba(34,34,34,0.78)] px-3 py-3 text-xs text-app-text transition hover:bg-[rgba(40,40,40,0.82)] focus:outline-none focus:ring-1 focus:ring-app-yellow/60 ${onRowClick ? "cursor-pointer" : ""} ${rowClassName}`}
                    style={{ gridTemplateColumns: resolvedTableColumns }}
                    onClick={
                      onRowClick ? () => onRowClick(row, index) : undefined
                    }
                    onKeyDown={
                      onRowClick
                        ? (event) => {
                            if (event.key === "Enter" || event.key === " ") {
                              event.preventDefault();
                              onRowClick(row, index);
                            }
                          }
                        : undefined
                    }
                  >
                    {columns.map((column) => (
                      <div
                        key={column.key}
                        className={`flex min-w-0 items-center ${getAlignClass(column.align)} ${cellClassName} ${column.className || ""}`}
                      >
                        <CellValue
                          column={column}
                          row={row}
                          value={row[column.key]}
                        />
                      </div>
                    ))}
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {totalPages > 0 && (
        <div
          className="flex flex-wrap items-center justify-center gap-4 px-4 pb-5 text-xs text-app-muted-light sm:gap-5"
          dir="ltr"
        >
          <button
            className={`text-app-yellow transition-colors hover:text-app-yellow/80 ${
              currentPage >= totalPages ? "cursor-not-allowed opacity-50" : ""
            }`}
            onClick={() =>
              onPageChange &&
              currentPage < totalPages &&
              onPageChange(currentPage + 1)
            }
            disabled={currentPage >= totalPages}
          >
            التالي →
          </button>
          <div className="flex flex-wrap items-center justify-center gap-1 sm:gap-2">
            {visiblePages.map((page, index) => (
              <span key={index}>
                {page === "..." ? (
                  <span className="px-1 py-1 sm:px-2">...</span>
                ) : (
                  <button
                    onClick={() => onPageChange && onPageChange(page)}
                    className={`rounded-md px-2 py-1 transition-colors ${
                      currentPage === page
                        ? "bg-app-card-soft text-white"
                        : "hover:bg-[rgba(40,40,40,0.82)]"
                    }`}
                  >
                    {page}
                  </button>
                )}
              </span>
            ))}
          </div>
          <button
            className={`transition-colors hover:text-white ${
              currentPage <= 1 ? "cursor-not-allowed opacity-50" : ""
            }`}
            onClick={() =>
              onPageChange && currentPage > 1 && onPageChange(currentPage - 1)
            }
            disabled={currentPage <= 1}
          >
            ← السابق
          </button>
        </div>
      )}
    </section>
  );
}

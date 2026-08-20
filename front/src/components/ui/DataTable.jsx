"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Button from "@/components/ui/Button";
import { DownloadIcon, FilterIcon, PlusIcon, SearchIcon, SortIcon } from "@/components/icons/Icons";
import { Field } from "@/components/forms/Field";
import SkeletonPage from "@/components/ui/Skeleton";
import Dropdown from "./Dropdown";

const statusClass = {
  مدفوع: "bg-[rgba(19,172,73,0.18)] text-app-green",
  معلق: "bg-[rgba(252,153,3,0.18)] text-[#d99300]",
  متأخر: "bg-[rgba(228,0,0,0.18)] text-app-red",
  "قيد المراجعة": "bg-[rgba(242,220,46,0.18)] text-app-yellow",
};

function getVisiblePages(page, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  if (page <= 3) return [1, 2, 3, 4, "...", total - 1, total];
  if (page >= total - 2) return [1, 2, "...", total - 3, total - 2, total - 1, total];
  return [1, "...", page - 1, page, page + 1, "...", total];
}

function getAlignClass(align = "start") {
  if (align === "center") return "justify-center text-center";
  if (align === "end") return "justify-end text-end";
  return "justify-start text-start";
}

function CellValue({ column, row, value, card = false, rowNumber }) {
  if (column.type === "rowNumber") return <span dir="ltr">{rowNumber}</span>;
  if (column.render) return column.render(value, row, column);

  if (column.type === "status") {
    return (
      <span
        className={`inline-flex rounded-md px-3 py-1 text-xs font-medium ${
          statusClass[value] || "bg-app-card-hover text-app-muted"
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

  if (value && typeof value === "object" && !(value instanceof Date)) {
    const stringVal = value.ar || value.en || value.name || value.label || value.title || "";
    return <span className={`min-w-0 ${card ? "break-words" : "truncate"}`}>{stringVal}</span>;
  }

  return <span className={`min-w-0 ${card ? "break-words" : "truncate"}`}>{value}</span>;
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
    <div className="flex flex-col gap-4 border-b border-app-line px-4 py-4 xl:flex-row xl:items-start xl:justify-between">
      <div className="min-w-0 text-start">
        <h2 className="text-base font-medium text-app-text">{title}</h2>
        {subtitle && <p className="mt-1 text-xs text-app-muted">{subtitle}</p>}

        <div className="mt-4 grid grid-cols-2 items-center gap-3 sm:flex sm:flex-wrap sm:justify-start">
          {showAdd && (
            <Button
              onClick={onAdd}
              icon={<PlusIcon className="size-4" />}
              className="h-9 w-full px-3 text-xs sm:w-auto"
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
              className="col-span-2 w-full sm:col-auto sm:min-w-48 sm:flex-1 xl:flex-none"
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
              className="h-9 w-full px-3 text-xs sm:w-auto"
            >
              تصفية
            </Button>
          )}

          {showExport && (
            <Button
              tone="ghost"
              icon={<DownloadIcon className="size-4" />}
              className="h-9 w-full px-3 text-xs sm:w-auto"
            >
              تصدير
            </Button>
          )}
        </div>
      </div>

      {toolbarMeta && <div className="shrink-0 text-start xl:pt-1">{toolbarMeta}</div>}
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
  currentPage,
  totalPages,
  onPageChange,
  onRowClick,
  getRowKey,
  tableColumns,
  minWidth = "780px",
  isLoading = false,
  loadingRows = 5,
  pagination = true,
  pageSize = 10,
  pageSizeOptions = [5, 10, 20, 50],
  totalItems,
  emptyMessage = "لا توجد بيانات",
  headerClassName = "",
  rowClassName = "",
  cellClassName = "",
  sortable = true,
  sortColumn,
  sortDirection,
  onSortChange,
  defaultSortColumn = null,
  defaultSortDirection = "asc",
  desktopScrollable = true,
}) {
  const [internalPage, setInternalPage] = useState(1);
  const [rowsPerPage, setRowsPerPage] = useState(pageSize);

  const [internalSortColumn, setInternalSortColumn] = useState(defaultSortColumn);
  const [internalSortDirection, setInternalSortDirection] = useState(defaultSortDirection);

  const activeSortColumn = sortColumn !== undefined ? sortColumn : internalSortColumn;
  const activeSortDirection = sortDirection !== undefined ? sortDirection : internalSortDirection;

  const handleHeaderClick = (column) => {
    if (!sortable || column.sortable === false || column.key === "actions") return;
    const colKey = typeof column.sortKey === "string" ? column.sortKey : column.key;

    let nextColumn = activeSortColumn;
    let nextDirection = "asc";

    if (activeSortColumn === colKey) {
      if (activeSortDirection === "asc") {
        nextDirection = "desc";
      } else if (activeSortDirection === "desc") {
        nextColumn = null;
        nextDirection = null;
      } else {
        nextDirection = "asc";
      }
    } else {
      nextColumn = colKey;
      nextDirection = "asc";
    }

    if (typeof onSortChange === "function") {
      onSortChange(nextColumn, nextDirection);
    } else {
      setInternalSortColumn(nextColumn);
      setInternalSortDirection(nextDirection);
    }
  };

  const dropdownOptions = useMemo(() => {
    return pageSizeOptions.map((option) => ({
      value: option,
      label: String(option),
    }));
  }, [pageSizeOptions]);

  const hasControlledPagination =
    typeof onPageChange === "function" && Number.isFinite(totalPages) && totalPages > 0;

  const sortedRows = useMemo(() => {
    if (
      !sortable ||
      !activeSortColumn ||
      !activeSortDirection ||
      !Array.isArray(rows) ||
      rows.length === 0
    ) {
      return rows || [];
    }

    if (typeof onSortChange === "function") {
      return rows;
    }

    const colDef = columns.find(
      (col) => (typeof col.sortKey === "string" ? col.sortKey : col.key) === activeSortColumn,
    );

    if (!colDef) {
      return rows;
    }

    const extractValue = (row) => {
      if (!row) return null;
      if (typeof colDef.sortValue === "function") {
        return colDef.sortValue(row, row[colDef.key]);
      }
      if (typeof colDef.sortKey === "function") {
        return colDef.sortKey(row);
      }

      const raw =
        row[colDef.key] ??
        (typeof colDef.sortKey === "string" ? row[colDef.sortKey] : undefined);

      if (raw && typeof raw === "object" && !(raw instanceof Date)) {
        if (typeof raw.ar === "string" || typeof raw.en === "string") {
          return raw.ar || raw.en;
        }
        if (typeof raw.name === "string" || (raw.name && typeof raw.name === "object")) {
          return typeof raw.name === "string" ? raw.name : raw.name.ar || raw.name.en || "";
        }
        if (typeof raw.full_name === "string") {
          return raw.full_name;
        }
        if (typeof raw.label === "string") {
          return raw.label;
        }
        if (typeof raw.title === "string") {
          return raw.title;
        }
      }
      return raw;
    };

    const normalizeSortValue = (val) => {
      if (val === null || val === undefined || val === "") return null;
      if (val instanceof Date) return val.getTime();
      if (typeof val === "boolean") return val ? 1 : 0;
      if (typeof val === "number") return isNaN(val) ? null : val;

      const str = String(val).trim();
      if (!str) return null;

      const cleanedNumeric = str.replace(/[$,\s]|(ل\.س|ر\.س|USD|SAR|SYP|EUR|GBP)/gi, "").trim();
      if (
        cleanedNumeric !== "" &&
        !isNaN(Number(cleanedNumeric)) &&
        !isNaN(parseFloat(cleanedNumeric))
      ) {
        return parseFloat(cleanedNumeric);
      }

      if (/^\d{4}-\d{2}-\d{2}/.test(str)) {
        const timestamp = Date.parse(str);
        if (!isNaN(timestamp)) return timestamp;
      }

      return str;
    };

    return [...rows].sort((a, b) => {
      const valA = normalizeSortValue(extractValue(a));
      const valB = normalizeSortValue(extractValue(b));

      if (valA === null && valB === null) return 0;
      if (valA === null) return 1;
      if (valB === null) return -1;

      let result = 0;
      if (typeof valA === "number" && typeof valB === "number") {
        result = valA - valB;
      } else {
        result = String(valA).localeCompare(String(valB), "ar", {
          numeric: true,
          sensitivity: "base",
        });
      }

      return activeSortDirection === "asc" ? result : -result;
    });
  }, [rows, sortable, activeSortColumn, activeSortDirection, onSortChange, columns]);

  const rowsSignature = sortedRows
    .map((row, index) => getRowKey?.(row, index) ?? row.id ?? row.key ?? index)
    .join("|");
  const previousRowsSignature = useRef(rowsSignature);
  const internalTotalPages = Math.max(1, Math.ceil(sortedRows.length / Math.max(rowsPerPage, 1)));
  const resolvedTotalPages = hasControlledPagination ? totalPages : internalTotalPages;
  const resolvedCurrentPage = hasControlledPagination
    ? Math.min(Math.max(currentPage || 1, 1), resolvedTotalPages)
    : Math.min(internalPage, resolvedTotalPages);
  const displayedRows = hasControlledPagination
    ? sortedRows
    : sortedRows.slice((resolvedCurrentPage - 1) * rowsPerPage, resolvedCurrentPage * rowsPerPage);
  const hasKnownTotalItems = !hasControlledPagination || Number.isFinite(totalItems);
  const resolvedTotalItems = totalItems ?? sortedRows.length;
  const firstVisibleItem = resolvedTotalItems > 0 ? (resolvedCurrentPage - 1) * rowsPerPage + 1 : 0;
  const lastVisibleItem = hasControlledPagination
    ? Math.min(firstVisibleItem + Math.max(displayedRows.length - 1, 0), resolvedTotalItems)
    : Math.min(resolvedCurrentPage * rowsPerPage, resolvedTotalItems);

  useEffect(() => {
    if (!hasControlledPagination) {
      setInternalPage(1);
    }
  }, [activeSortColumn, activeSortDirection, hasControlledPagination]);

  useEffect(() => {
    if (!hasControlledPagination) {
      setInternalPage((page) => Math.min(page, internalTotalPages));
    }
  }, [hasControlledPagination, internalTotalPages]);

  useEffect(() => {
    if (!hasControlledPagination && previousRowsSignature.current !== rowsSignature) {
      previousRowsSignature.current = rowsSignature;
      setInternalPage(1);
    }
  }, [hasControlledPagination, rowsSignature]);

  const changePage = (page) => {
    const nextPage = Math.min(Math.max(page, 1), resolvedTotalPages);
    if (hasControlledPagination) {
      onPageChange(nextPage);
      return;
    }
    setInternalPage(nextPage);
  };

  const resolvedTableColumns =
    tableColumns || columns.map((column) => column.width || "1fr").join(" ");
  const visiblePages =
    resolvedTotalPages > 0 ? getVisiblePages(resolvedCurrentPage, resolvedTotalPages) : [];

  return (
    <section className="app-card overflow-hidden rounded-2xl" dir="rtl">
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

      <div className="hidden px-4 pb-4 xl:block">
        <div className={desktopScrollable ? "overflow-x-auto scrollbar-thin" : "overflow-x-hidden"}>
          <div className="w-full max-w-full" style={{ minWidth }}>
            <div
              className={`grid border-b border-app-line px-3 py-3 text-xs text-app-muted-light ${headerClassName}`}
              style={{ gridTemplateColumns: resolvedTableColumns }}
            >
              {columns.map((column) => {
                const colKey = typeof column.sortKey === "string" ? column.sortKey : column.key;
                const isColumnSortable =
                  sortable && column.sortable !== false && column.key !== "actions";
                const isSorted = isColumnSortable && activeSortColumn === colKey;
                const currentDir = isSorted ? activeSortDirection : null;

                return (
                  <div
                    key={column.key}
                    data-testid={`data-table-header-${column.key}`}
                    className={`group flex min-w-0 items-center gap-1.5 ${getAlignClass(column.align)} ${
                      isColumnSortable
                        ? "cursor-pointer select-none transition-colors hover:text-app-text"
                        : ""
                    }`}
                    onClick={isColumnSortable ? () => handleHeaderClick(column) : undefined}
                    title={isColumnSortable ? `ترتيب حسب ${column.label}` : undefined}
                  >
                    <span className="truncate">{column.label}</span>
                    {isColumnSortable && (
                      <SortIcon direction={currentDir} className="shrink-0 size-3.5" />
                    )}
                  </div>
                );
              })}
            </div>

            {isLoading ? (
              <SkeletonPage
                className="space-y-0"
                blocks={[{ type: "list", count: loadingRows, itemClassName: "h-16" }]}
              />
            ) : displayedRows.length === 0 ? (
              <div className="mt-3 rounded-xl border border-app-line bg-app-card-soft/60 p-8 text-center text-sm text-app-muted-light">
                {emptyMessage}
              </div>
            ) : (
              <div className="space-y-3 pt-3">
                {displayedRows.map((row, index) => {
                  const key = getRowKey?.(row, index) ?? `${row.id || index}-${index}`;

                  return (
                    <div
                      key={key}
                      data-testid="data-table-row"
                      role={onRowClick ? "button" : undefined}
                      tabIndex={onRowClick ? 0 : undefined}
                      className={`grid w-full items-center rounded-lg border border-transparent bg-app-card-soft px-3 py-3 text-xs text-app-text transition hover:border-app-line hover:bg-app-card-hover focus:outline-none focus:ring-1 focus:ring-app-yellow/60 ${onRowClick ? "cursor-pointer" : ""} ${rowClassName}`}
                      style={{ gridTemplateColumns: resolvedTableColumns }}
                      onClick={onRowClick ? () => onRowClick(row, index) : undefined}
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
                          onClick={
                            column.key === "actions"
                              ? (event) => event.stopPropagation()
                              : undefined
                          }
                        >
                          <CellValue
                            column={column}
                            row={row}
                            value={row[column.key]}
                            rowNumber={firstVisibleItem + index}
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
      </div>

      <div className="space-y-3 px-3 py-3 sm:px-4 xl:hidden">
        {isLoading ? (
          <SkeletonPage
            className="space-y-3"
            blocks={[
              {
                type: "list",
                count: Math.min(loadingRows, rowsPerPage),
                itemClassName: "h-44 rounded-xl",
              },
            ]}
          />
        ) : displayedRows.length === 0 ? (
          <div className="rounded-xl border border-app-line bg-app-card-soft/60 p-8 text-center text-sm text-app-muted-light">
            {emptyMessage}
          </div>
        ) : (
          displayedRows.map((row, index) => {
            const key = getRowKey?.(row, index) ?? `${row.id || index}-${index}`;

            return (
              <article
                key={key}
                data-testid="data-table-card"
                role={onRowClick ? "button" : undefined}
                tabIndex={onRowClick ? 0 : undefined}
                className={`rounded-xl border border-app-line bg-app-card-soft/70 p-3 text-xs text-app-text shadow-sm transition hover:border-app-yellow/40 hover:bg-app-card-hover focus:outline-none focus:ring-2 focus:ring-app-yellow/50 ${onRowClick ? "cursor-pointer" : ""}`}
                onClick={onRowClick ? () => onRowClick(row, index) : undefined}
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
                <dl className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                  {columns.map((column) => (
                    <div
                      key={column.key}
                      className={`min-w-0 rounded-lg border border-app-line/70 bg-app-card/70 p-3 ${column.key === "actions" ? "sm:col-span-2" : ""}`}
                      onClick={
                        column.key === "actions" ? (event) => event.stopPropagation() : undefined
                      }
                    >
                      <dt className="mb-2 text-[11px] font-medium text-app-muted">
                        {column.label}
                      </dt>
                      <dd
                        className={`flex min-h-6 min-w-0 items-center ${getAlignClass(column.align)} ${cellClassName} ${column.className || ""}`}
                      >
                        <CellValue
                          column={column}
                          row={row}
                          value={row[column.key]}
                          card
                          rowNumber={firstVisibleItem + index}
                        />
                      </dd>
                    </div>
                  ))}
                </dl>
              </article>
            );
          })
        )}
      </div>

      {pagination && !isLoading && (resolvedTotalItems > 0 || hasControlledPagination) && (
        <div
          className="flex flex-col gap-4 border-t border-app-line px-3 py-4 text-xs text-app-muted-light sm:px-4 xl:flex-row xl:items-center xl:justify-between"
          dir="rtl"
        >
          <div className="flex flex-wrap items-center justify-center gap-3 xl:justify-start">
            <span>
              {hasKnownTotalItems
                ? `عرض ${firstVisibleItem}–${lastVisibleItem} من ${resolvedTotalItems}`
                : `الصفحة ${resolvedCurrentPage} من ${resolvedTotalPages}`}
            </span>
            {!hasControlledPagination && pageSizeOptions.length > 0 && (
              <div className="inline-flex items-center gap-2">
                <span>صفوف الصفحة</span>
                <Dropdown
                  value={rowsPerPage}
                  onChange={(val) => {
                    setRowsPerPage(Number(val));
                    setInternalPage(1);
                  }}
                  options={dropdownOptions}
                  className="w-16 text-app-text"
                  buttonClassName="h-8 bg-app-panel-soft/40 border border-app-line rounded-lg px-2 text-xs"
                  menuClassName="bottom-full mb-2 !mt-0"
                />
              </div>
            )}
          </div>

          <nav
            className="flex flex-wrap items-center justify-center gap-2"
            aria-label="ترقيم صفحات الجدول"
            dir="rtl"
          >
            <button
              className="inline-flex h-8 items-center justify-center rounded-lg px-3 text-xs transition app-input hover:border-app-yellow disabled:cursor-not-allowed disabled:opacity-40"
              onClick={() => changePage(resolvedCurrentPage - 1)}
              disabled={resolvedCurrentPage <= 1}
              aria-label="الصفحة السابقة"
            >
              السابق
            </button>
            <div className="flex flex-wrap items-center justify-center gap-1">
              {visiblePages.map((page, index) => (
                <span key={`${page}-${index}`}>
                  {page === "..." ? (
                    <span className="px-1 py-1 sm:px-2">...</span>
                  ) : (
                    <button
                      onClick={() => changePage(page)}
                      aria-current={resolvedCurrentPage === page ? "page" : undefined}
                      aria-label={`الصفحة ${page}`}
                      className={`inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-center text-xs transition-colors ${
                        resolvedCurrentPage === page
                          ? "bg-app-yellow font-bold text-app-bg"
                          : "app-input hover:border-app-yellow"
                      }`}
                    >
                      {page}
                    </button>
                  )}
                </span>
              ))}
            </div>
            <button
              className="inline-flex h-8 items-center justify-center rounded-lg px-3 text-xs transition app-input hover:border-app-yellow disabled:cursor-not-allowed disabled:opacity-40"
              onClick={() => changePage(resolvedCurrentPage + 1)}
              disabled={resolvedCurrentPage >= resolvedTotalPages}
              aria-label="الصفحة التالية"
            >
              التالي
            </button>
          </nav>
        </div>
      )}
    </section>
  );
}

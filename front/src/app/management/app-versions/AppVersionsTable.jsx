"use client";

import { useMemo } from "react";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import RowActions from "@/components/ui/RowActions";
import SearchInput from "@/components/ui/SearchInput";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import {
  AndroidIcon,
  AppleIcon,
  DownloadIcon,
  FilterIcon,
  GiftIcon,
  PlusIcon,
} from "@/components/icons/Icons";
import { formatPublishDate } from "./appVersionsUtils";

const PLATFORM_OPTIONS = [
  { value: "all", label: "كل الأنظمة" },
  { value: "android", label: "Android" },
  { value: "ios", label: "iOS" },
];

const STATUS_OPTIONS = [
  { value: "all", label: "كل الحالات" },
  { value: "active", label: "نشط" },
  { value: "inactive", label: "غير نشط" },
];

function PlatformBadge({ platform, iconOnly = false }) {
  const isAndroid = platform === "android";
  const Icon = isAndroid ? AndroidIcon : AppleIcon;

  return (
    <span
      className={`inline-flex items-center justify-center gap-2 rounded-lg border px-2.5 py-1.5 text-xs font-medium ${
        isAndroid
          ? "border-app-green/20 bg-app-green/10 text-app-green"
          : "border-app-blue/20 bg-app-blue/10 text-app-blue"
      }`}
    >
      <Icon className="size-4" />
      {!iconOnly && <span>{isAndroid ? "Android" : "iOS"}</span>}
    </span>
  );
}

function EmptyMessage({ filtered, onReset, onCreate }) {
  return (
    <div className="flex min-h-48 flex-col items-center justify-center py-4 text-center">
      <span className="grid size-14 place-items-center rounded-2xl bg-app-yellow-soft text-app-yellow">
        <GiftIcon className="size-7" />
      </span>
      <h3 className="mt-4 text-base font-medium text-app-text">
        {filtered ? "لا توجد نتائج مطابقة" : "لا توجد إصدارات مسجلة"}
      </h3>
      <p className="mt-2 max-w-md text-xs leading-6 text-app-muted-light">
        {filtered
          ? "جرّب تغيير عبارة البحث أو إزالة أحد عوامل التصفية."
          : "أضف أول إصدار لتطبيق المتدرب وحدد النظام ورقم البناء وطريقة التنزيل."}
      </p>
      {filtered ? (
        <Button type="button" tone="outline" className="mt-4 h-9" onClick={onReset}>
          مسح عوامل التصفية
        </Button>
      ) : onCreate ? (
        <Button
          type="button"
          className="mt-4 h-9 text-black"
          icon={<PlusIcon className="size-4" />}
          onClick={onCreate}
        >
          إضافة الإصدار الأول
        </Button>
      ) : null}
    </div>
  );
}

export default function AppVersionsTable({
  versions = [],
  totalVersions = 0,
  search,
  onSearchChange,
  platformFilter,
  onPlatformFilterChange,
  statusFilter,
  onStatusFilterChange,
  hasActiveFilters = false,
  onResetFilters,
  onCreate,
  onEdit,
  onDelete,
  onToggleStatus,
  canUpdate = true,
  canDelete = true,
  isLoading = false,
  isToggling = false,
}) {
  const columns = useMemo(
    () => [
      {
        key: "version_number",
        label: "الإصدار",
        align: "start",
        sortValue: (item) => item.version_number || "",
        render: (_, item) => (
          <div className="flex min-w-0 items-center gap-3">
            <PlatformBadge platform={item.platform} iconOnly />
            <div className="min-w-0 text-start">
              <div className="flex flex-wrap items-center gap-2">
                <bdi className="text-sm font-semibold text-app-text">v{item.version_number}</bdi>
                <bdi className="rounded-md bg-app-panel px-2 py-0.5 text-[10px] text-app-muted">
                  Build #{item.build_number}
                </bdi>
              </div>
              <p className="mt-1 truncate text-[11px] text-app-muted-light">
                {item.release_notes || item.app_name || "تطبيق المتدرب"}
              </p>
            </div>
          </div>
        ),
      },
      {
        key: "platform",
        label: "النظام",
        align: "center",
        render: (value) => <PlatformBadge platform={value} />,
      },
      {
        key: "is_force_update",
        label: "نوع التحديث",
        align: "center",
        sortValue: (item) => Number(Boolean(item.is_force_update)),
        render: (value) => (
          <span
            className={`inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium ${
              value ? "bg-app-red/10 text-app-red" : "bg-app-card-hover text-app-muted-light"
            }`}
          >
            {value ? "إجباري" : "اختياري"}
          </span>
        ),
      },
      {
        key: "download_url",
        label: "ملف التطبيق",
        align: "center",
        sortable: false,
        render: (value, item) =>
          value ? (
            <a
              href={value}
              target="_blank"
              rel="noreferrer"
              download
              className="inline-flex items-center gap-2 rounded-lg border border-app-line bg-app-panel px-3 py-2 text-xs text-app-text transition hover:border-app-yellow/60 hover:text-app-yellow"
              onClick={(event) => event.stopPropagation()}
            >
              <DownloadIcon className="size-4" />
              <span>{item.formatted_file_size || "تنزيل"}</span>
            </a>
          ) : (
            <span className="text-xs text-app-muted">غير متاح</span>
          ),
      },
      {
        key: "is_active",
        label: "الحالة",
        align: "center",
        sortValue: (item) => Number(Boolean(item.is_active)),
        render: (value, item) => (
          <div className="inline-flex items-center gap-2">
            <ToggleSwitch
              checked={Boolean(value)}
              onChange={() => onToggleStatus?.(item)}
              disabled={!canUpdate || isToggling}
              size="sm"
              ariaLabel={`تغيير حالة الإصدار ${item.version_number}`}
            />
            <span className={`text-xs font-medium ${value ? "text-app-green" : "text-app-muted"}`}>
              {value ? "نشط" : "متوقف"}
            </span>
          </div>
        ),
      },
      {
        key: "created_at",
        label: "تاريخ النشر",
        align: "center",
        sortValue: (item) => item.created_at || "",
        render: (value) => (
          <span className="text-xs leading-5 text-app-muted-light">{formatPublishDate(value)}</span>
        ),
      },
      {
        key: "actions",
        label: "الإجراءات",
        align: "center",
        sortable: false,
        render: (_, item) => (
          <RowActions
            onEdit={canUpdate ? () => onEdit?.(item) : undefined}
            onDelete={canDelete ? () => onDelete?.(item) : undefined}
            editTitle="تعديل الإصدار"
            deleteTitle="حذف الإصدار"
          />
        ),
      },
    ],
    [canDelete, canUpdate, isToggling, onDelete, onEdit, onToggleStatus],
  );

  return (
    <DataTable
      title="سجل الإصدارات"
      subtitle="راجع النسخ المنشورة وحالة توفرها للمتدربين."
      columns={columns}
      rows={versions}
      minWidth="980px"
      tableColumns="minmax(240px,1.7fr) .75fr .75fr .9fr .8fr 1fr .65fr"
      defaultSortColumn="created_at"
      defaultSortDirection="desc"
      pagination={false}
      showAdd={false}
      showSearch={false}
      showFilter={false}
      showExport={false}
      isLoading={isLoading}
      emptyMessage={
        <EmptyMessage filtered={hasActiveFilters} onReset={onResetFilters} onCreate={onCreate} />
      }
      toolbarActions={
        <div className="col-span-2 flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
          <SearchInput
            value={search}
            onChange={(event) => onSearchChange?.(event.target.value)}
            placeholder="ابحث برقم الإصدار أو الملاحظات..."
            className="sm:min-w-72"
          />
          <Dropdown
            value={platformFilter}
            options={PLATFORM_OPTIONS}
            onChange={onPlatformFilterChange}
            icon={FilterIcon}
            className="min-w-40 text-app-text"
            ariaLabel="تصفية حسب نظام التشغيل"
          />
          <Dropdown
            value={statusFilter}
            options={STATUS_OPTIONS}
            onChange={onStatusFilterChange}
            icon={FilterIcon}
            className="min-w-40 text-app-text"
            ariaLabel="تصفية حسب حالة الإصدار"
          />
        </div>
      }
      toolbarMeta={
        <p className="text-sm text-app-muted-light">
          النتائج:{" "}
          <span className="font-medium text-app-text">{versions.length.toLocaleString("ar")}</span>
          <span className="mx-1">من</span>
          <span className="font-medium text-app-text">{totalVersions.toLocaleString("ar")}</span>
        </p>
      }
      getRowKey={(item) => item.id}
      rowClassName="gap-2 px-3 py-3"
      headerClassName="gap-2 px-3"
    />
  );
}

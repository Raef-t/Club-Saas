"use client";

import Dropdown from "@/components/ui/Dropdown";
import { TagIcon } from "@/components/icons/Icons";

export default function ReportFilterSelect({
  label,
  value,
  options,
  onChange,
  searchable = false,
  disabled = false,
}) {
  return (
    <div className="min-w-0 text-start">
      <span className="mb-3 flex items-center gap-2 text-base font-medium text-app-text">
        <TagIcon className="size-4 shrink-0 text-app-yellow" />
        <span>{label}</span>
      </span>
      <Dropdown
        value={value}
        options={options}
        onChange={onChange}
        searchable={searchable}
        disabled={disabled}
        searchPlaceholder={`ابحث في ${label}`}
        ariaLabel={label}
        buttonClassName="h-[46px] border border-app-muted/50 bg-app-panel-soft/40"
      />
    </div>
  );
}

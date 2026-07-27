"use client";

import Dropdown from "@/components/ui/Dropdown";
import SearchInput from "@/components/ui/SearchInput";
import { FilterIcon } from "@/components/icons/Icons";
import { LOCKER_FILTER_OPTIONS } from "./lockerConstants";

/**
 * Renders locker search, branch, and status filters.
 */
export default function LockerFilters({
  search,
  branchFilter,
  statusFilter,
  branchOptions,
  onSearchChange,
  onBranchChange,
  onStatusChange,
}) {
  return (
    <div className="flex flex-col gap-4 rounded-xl border border-app-line bg-app-card p-4 lg:flex-row lg:items-center lg:justify-between">
      <SearchInput
        placeholder="البحث برقم الخزانة..."
        value={search}
        onChange={(event) => onSearchChange(event.target.value)}
        className="w-full lg:max-w-sm"
      />

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="flex items-center gap-2 text-xs text-app-muted-light">
          <FilterIcon className="size-4" />
          <span>التصفية</span>
        </div>
        <Dropdown
          options={branchOptions}
          value={branchFilter}
          onChange={onBranchChange}
          className="w-full sm:w-[180px]"
        />
        <Dropdown
          options={LOCKER_FILTER_OPTIONS}
          value={statusFilter}
          onChange={onStatusChange}
          className="w-full sm:w-[180px]"
        />
      </div>
    </div>
  );
}

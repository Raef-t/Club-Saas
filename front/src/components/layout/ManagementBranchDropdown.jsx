"use client";

import Dropdown from "@/components/ui/Dropdown";
import { GridIcon } from "@/components/icons/Icons";
import { useOptionalManagementBranch } from "@/lib/ManagementBranchContext";
import { createManagementBranchOptions } from "@/lib/managementBranchUtils";
import { formatLocalizedName } from "@/lib/utils";

/**
 * Renders the global branch selector only inside the management shell.
 * When the user is not admin/super_admin, only their own assigned branch
 * is displayed as a clean static indicator without a selection menu.
 */
export default function ManagementBranchDropdown() {
  const branchContext = useOptionalManagementBranch();

  if (!branchContext) return null;

  const {
    canSelectBranches,
    branches,
    selectedBranch,
    selectedBranchId,
    setSelectedBranchId,
    isLoading,
  } = branchContext;

  if (!canSelectBranches || branches.length <= 1) {
    const currentBranchName = selectedBranch?.name
      ? formatLocalizedName(selectedBranch.name)
      : branches[0]?.name
        ? formatLocalizedName(branches[0].name)
        : "الفرع الحالي";

    return (
      <div
        className="flex h-10 w-full max-w-[240px] items-center gap-2 rounded-xl border border-app-line/40 bg-app-card-soft/80 px-3.5 text-xs font-medium text-app-text sm:w-[220px]"
        title={`الفرع الحالي: ${currentBranchName}`}
      >
        <GridIcon className="size-4 shrink-0 text-app-yellow" />
        <span className="truncate">{currentBranchName}</span>
      </div>
    );
  }

  return (
    <Dropdown
      value={selectedBranchId}
      onChange={setSelectedBranchId}
      options={createManagementBranchOptions(branches)}
      icon={GridIcon}
      searchable={branches.length > 7}
      disabled={isLoading}
      placeholder="اختر الفرع"
      className="w-full max-w-[240px] sm:w-[220px]"
      buttonClassName="h-10 border border-app-line/40 bg-app-card-soft/80"
      menuClassName="min-w-[220px]"
    />
  );
}

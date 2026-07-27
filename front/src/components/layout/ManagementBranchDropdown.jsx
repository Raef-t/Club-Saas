"use client";

import Dropdown from "@/components/ui/Dropdown";
import { GridIcon } from "@/components/icons/Icons";
import { useOptionalManagementBranch } from "@/lib/ManagementBranchContext";
import { createManagementBranchOptions } from "@/lib/managementBranchUtils";

/**
 * Renders the global branch selector only inside the management shell.
 */
export default function ManagementBranchDropdown() {
  const branchContext = useOptionalManagementBranch();

  if (!branchContext) return null;

  return (
    <Dropdown
      value={branchContext.selectedBranchId}
      onChange={branchContext.setSelectedBranchId}
      options={createManagementBranchOptions(branchContext.branches)}
      icon={GridIcon}
      searchable={branchContext.branches.length > 7}
      disabled={branchContext.isLoading}
      placeholder="اختر الفرع"
      className="w-full max-w-[240px] sm:w-[220px]"
      buttonClassName="h-10 border border-app-line/40 bg-app-card-soft/80"
      menuClassName="min-w-[220px]"
    />
  );
}

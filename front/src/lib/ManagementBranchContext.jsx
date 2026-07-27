"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { getBranchesArray } from "@/lib/utils";
import {
  ALL_BRANCHES_VALUE,
  MANAGEMENT_BRANCH_COOKIE,
  normalizeSelectedBranchId,
} from "@/lib/managementBranchUtils";

const ManagementBranchContext = createContext(null);

/**
 * Provides one persistent branch selection to the entire management section.
 */
export function ManagementBranchProvider({
  children,
  initialBranches,
  initialSelectedBranchId,
  cookieName = MANAGEMENT_BRANCH_COOKIE,
  cookiePath = "/management",
}) {
  const { currentData: branchesResponse, isLoading, isFetching } = useGetBranchesQuery();
  const branches = useMemo(
    () => getBranchesArray(branchesResponse || initialBranches),
    [branchesResponse, initialBranches],
  );
  const [selectedBranchId, setSelectedBranchIdState] = useState(() =>
    normalizeSelectedBranchId(initialSelectedBranchId, initialBranches),
  );

  useEffect(() => {
    const normalizedSelection = normalizeSelectedBranchId(selectedBranchId, branches);

    if (normalizedSelection !== selectedBranchId) {
      setSelectedBranchIdState(normalizedSelection);
    }
  }, [branches, selectedBranchId]);

  /**
   * Updates the global branch and persists it for subsequent requests.
   */
  const setSelectedBranchId = useCallback(
    (branchId) => {
      const normalizedSelection = normalizeSelectedBranchId(branchId, branches);
      setSelectedBranchIdState(normalizedSelection);
      document.cookie = `${cookieName}=${encodeURIComponent(
        normalizedSelection,
      )}; Path=${cookiePath}; Max-Age=31536000; SameSite=Lax`;
    },
    [branches, cookieName, cookiePath],
  );

  const selectedBranch = useMemo(
    () => branches.find((branch) => String(branch.id) === String(selectedBranchId)) || null,
    [branches, selectedBranchId],
  );
  const selectedClubId = selectedBranch?.club_id ?? selectedBranch?.club?.id ?? null;
  const value = useMemo(
    () => ({
      branches,
      selectedBranchId,
      selectedBranch,
      selectedClubId: selectedClubId == null ? null : String(selectedClubId),
      isAllBranches: selectedBranchId === ALL_BRANCHES_VALUE,
      isLoading: isLoading && branches.length === 0,
      isFetching,
      setSelectedBranchId,
    }),
    [
      branches,
      isFetching,
      isLoading,
      selectedBranch,
      selectedClubId,
      selectedBranchId,
      setSelectedBranchId,
    ],
  );

  return (
    <ManagementBranchContext.Provider value={value}>{children}</ManagementBranchContext.Provider>
  );
}

/**
 * Returns the required management branch context.
 */
export function useManagementBranch() {
  const context = useContext(ManagementBranchContext);

  if (!context) {
    throw new Error("useManagementBranch must be used within ManagementBranchProvider.");
  }

  return context;
}

/**
 * Returns the management branch context when the current shell provides one.
 */
export function useOptionalManagementBranch() {
  return useContext(ManagementBranchContext);
}

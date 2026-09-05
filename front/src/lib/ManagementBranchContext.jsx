"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetClubsQuery } from "@/lib/api/clubsApi";
import { withAllItems } from "@/lib/pagination";
import { getBrandClubs, resolveClubLogoUrl, selectBrandClub } from "@/lib/clubBranding";
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
  canSelectAllBranches = true,
  cookieName = MANAGEMENT_BRANCH_COOKIE,
  cookiePath = "/management",
}) {
  const { currentData: branchesResponse, isLoading, isFetching } =
    useGetBranchesQuery(withAllItems(), { skip: !canSelectAllBranches });
  const { currentData: clubsResponse, isFetching: isFetchingClubs } =
    useGetClubsQuery(withAllItems());
  const branches = useMemo(
    () =>
      canSelectAllBranches
        ? getBranchesArray(branchesResponse || initialBranches)
        : getBranchesArray(initialBranches),
    [branchesResponse, canSelectAllBranches, initialBranches],
  );
  const [selectedBranchId, setSelectedBranchIdState] = useState(() =>
    normalizeSelectedBranchId(initialSelectedBranchId, branches, {
      fallbackToFirst: !canSelectAllBranches,
    }),
  );

  useEffect(() => {
    const normalizedSelection = normalizeSelectedBranchId(selectedBranchId, branches, {
      fallbackToFirst: !canSelectAllBranches,
    });

    if (normalizedSelection !== selectedBranchId) {
      setSelectedBranchIdState(normalizedSelection);
    }
  }, [branches, canSelectAllBranches, selectedBranchId]);

  /**
   * Updates the global branch and persists it for subsequent requests.
   */
  const setSelectedBranchId = useCallback(
    (branchId) => {
      if (!canSelectAllBranches) return;
      const normalizedSelection = normalizeSelectedBranchId(branchId, branches);
      setSelectedBranchIdState(normalizedSelection);
      document.cookie = `${cookieName}=${encodeURIComponent(
        normalizedSelection,
      )}; Path=${cookiePath}; Max-Age=31536000; SameSite=Lax`;
    },
    [branches, canSelectAllBranches, cookieName, cookiePath],
  );

  const selectedBranch = useMemo(
    () => branches.find((branch) => String(branch.id) === String(selectedBranchId)) || null,
    [branches, selectedBranchId],
  );
  const selectedClubId = selectedBranch?.club_id ?? selectedBranch?.club?.id ?? null;
  const clubs = useMemo(() => getBrandClubs(clubsResponse), [clubsResponse]);
  const selectedClub = useMemo(
    () => selectBrandClub(clubs, selectedClubId, selectedBranch?.club),
    [clubs, selectedBranch, selectedClubId],
  );
  const brandLogoUrl = useMemo(() => resolveClubLogoUrl(selectedClub), [selectedClub]);
  const value = useMemo(
    () => ({
      branches,
      selectedBranchId,
      selectedBranch,
      selectedClubId: selectedClubId == null ? null : String(selectedClubId),
      selectedClub,
      brandLogoUrl,
      canSelectBranches: Boolean(canSelectAllBranches),
      isAllBranches: canSelectAllBranches && selectedBranchId === ALL_BRANCHES_VALUE,
      isLoading: canSelectAllBranches && isLoading && branches.length === 0,
      isFetching: (canSelectAllBranches && isFetching) || isFetchingClubs,
      setSelectedBranchId,
    }),
    [
      brandLogoUrl,
      branches,
      canSelectAllBranches,
      isFetching,
      isFetchingClubs,
      isLoading,
      selectedBranch,
      selectedClub,
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

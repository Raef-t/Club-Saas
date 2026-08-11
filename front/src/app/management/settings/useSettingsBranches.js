import { useMemo } from "react";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getBranchesArray } from "@/lib/utils";

/**
 * Coordinates the shared branch selector and keeps server data visible during hydration.
 */
export function useSettingsBranches(initialBranches) {
  const {
    isAllBranches,
    selectedBranchId: globalBranchId,
    setSelectedBranchId,
  } = useManagementBranch();
  const {
    currentData: branchesResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetBranchesQuery();
  const branches = useMemo(
    () => getBranchesArray(branchesResponse || initialBranches),
    [branchesResponse, initialBranches],
  );
  const selectedBranchId = isAllBranches ? "" : globalBranchId;

  return {
    branches,
    selectedBranchId,
    setSelectedBranchId,
    isLoading: isLoading && branches.length === 0,
    isFetching,
    errorMessage:
      error && branches.length === 0 ? getApiErrorMessage(error, "تعذر تحميل فروع النادي.") : "",
    retry: refetch,
  };
}

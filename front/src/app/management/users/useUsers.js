import { useMemo, useState } from "react";
import { useGetUsersQuery } from "@/lib/api/usersApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { getApiErrorMessage } from "@/lib/apiError";
import { ALL_BRANCHES_VALUE } from "@/lib/managementBranchUtils";
import {
  createUserRoleOptions,
  createUserStats,
  filterUsers,
  getUsersCollection,
} from "./usersUtils";

export function useUsers({ initialUsers } = {}) {
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState("all");
  const { selectedBranchId } = useManagementBranch();

  const baseQueryParams = useMemo(() => {
    const params = {};
    if (selectedBranchId && selectedBranchId !== ALL_BRANCHES_VALUE) {
      params.branch_id = selectedBranchId;
    }
    return params;
  }, [selectedBranchId]);

  const roleQueryParams = useMemo(() => {
    const params = { ...baseQueryParams };
    if (roleFilter !== "all") {
      params.role = roleFilter;
    }
    return params;
  }, [baseQueryParams, roleFilter]);

  const {
    currentData: allUsersResponse,
    error: allUsersError,
    isLoading: isLoadingAllUsers,
    isFetching: isFetchingAllUsers,
    refetch: refetchAllUsers,
  } = useGetUsersQuery(baseQueryParams);
  const {
    currentData: roleUsersResponse,
    error: roleUsersError,
    isLoading: isLoadingRoleUsers,
    isFetching: isFetchingRoleUsers,
    refetch: refetchRoleUsers,
  } = useGetUsersQuery(roleQueryParams, {
    skip: roleFilter === "all",
  });

  const allUsers = useMemo(
    () => getUsersCollection(allUsersResponse || initialUsers),
    [allUsersResponse, initialUsers],
  );
  const roleUsers = useMemo(
    () => (roleFilter === "all" ? allUsers : getUsersCollection(roleUsersResponse)),
    [allUsers, roleFilter, roleUsersResponse],
  );
  const users = useMemo(() => filterUsers(roleUsers, search), [roleUsers, search]);
  const stats = useMemo(() => createUserStats(allUsers), [allUsers]);
  const roleOptions = useMemo(() => createUserRoleTabs(allUsers), [allUsers]);
  const isRoleFiltered = roleFilter !== "all";
  const activeError = isRoleFiltered ? roleUsersError : allUsersError;
  const hasVisibleSource = isRoleFiltered ? Boolean(roleUsersResponse) : allUsers.length > 0;

  function retry() {
    if (isRoleFiltered) {
      refetchRoleUsers();
      return;
    }

    refetchAllUsers();
  }

  return {
    search,
    setSearch,
    roleFilter,
    setRoleFilter,
    users,
    stats,
    roleOptions,
    totalResults: users.length,
    isLoading:
      !hasVisibleSource &&
      (isRoleFiltered
        ? isLoadingRoleUsers || isFetchingRoleUsers
        : isLoadingAllUsers || isFetchingAllUsers),
    isRefreshing: isRoleFiltered ? isFetchingRoleUsers : isFetchingAllUsers,
    errorMessage: activeError
      ? getApiErrorMessage(activeError, "تعذر تحميل قائمة حسابات المستخدمين.")
      : "",
    retry,
  };
}

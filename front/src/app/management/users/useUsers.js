import { useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import { useGetUsersQuery } from "@/lib/api/usersApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { buildUserRoleTabs, createUserStats, filterUsers, getUsersCollection } from "./usersUtils";
import { getPaginationMeta, useServerPagination, withAllItems } from "@/lib/pagination";

export function useUsers({ initialUsers } = {}) {
  const searchParams = useSearchParams();
  const urlRole = searchParams?.get("role");
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState(urlRole || "all");
  const paginationFilterKey = [roleFilter, search].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);
  const needsAllUsers = Boolean(search.trim());
  const {
    currentData: allUsersResponse,
    error: allUsersError,
    isLoading: isLoadingAllUsers,
    isFetching: isFetchingAllUsers,
    refetch: refetchAllUsers,
  } = useGetUsersQuery(withAllItems());
  const {
    currentData: listUsersResponse,
    error: listUsersError,
    isLoading: isLoadingListUsers,
    isFetching: isFetchingListUsers,
    refetch: refetchListUsers,
  } = useGetUsersQuery({
    ...(roleFilter !== "all" ? { role: roleFilter } : {}),
    ...(needsAllUsers ? { per_page: "all" } : { page, per_page: perPage }),
  });

  const allUsers = useMemo(
    () => getUsersCollection(allUsersResponse || initialUsers),
    [allUsersResponse, initialUsers],
  );
  const canUseInitialUsers =
    !needsAllUsers && page === 1 && perPage === 15 && roleFilter === "all";
  const listResponse = listUsersResponse || (canUseInitialUsers ? initialUsers : null);
  const pageUsers = useMemo(() => getUsersCollection(listResponse), [listResponse]);
  const users = useMemo(() => filterUsers(pageUsers, search), [pageUsers, search]);
  const pagination = useMemo(
    () => getPaginationMeta(listResponse, { page, perPage }),
    [listResponse, page, perPage],
  );
  const stats = useMemo(
    () => createUserStats(allUsers, { roleFilter, setRoleFilter }),
    [allUsers, roleFilter],
  );
  const roleOptions = useMemo(() => buildUserRoleTabs(allUsers), [allUsers]);
  const activeError = listUsersError || allUsersError;
  const hasVisibleSource = Boolean(listResponse);

  function retry() {
    refetchListUsers();
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
    pagination: { ...pagination, setPage, setPerPage },
    totalResults: needsAllUsers ? users.length : pagination.total,
    isLoading:
      !hasVisibleSource && (isLoadingListUsers || isFetchingListUsers || isLoadingAllUsers),
    isRefreshing: isFetchingListUsers || isFetchingAllUsers,
    errorMessage: activeError
      ? getApiErrorMessage(activeError, "تعذر تحميل قائمة حسابات المستخدمين.")
      : "",
    retry,
  };
}

"use client";

import { useMemo, useState } from "react";
import { useGetUsersQuery } from "@/lib/api/usersApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { createUserRoleOptions, createUserStats, filterUsers, getUsersCollection, USER_ROLE_TAB_LABELS } from "./usersUtils";

export function useUsers({ initialUsers } = {}) {
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState("all");
  const {
    currentData: allUsersResponse,
    error: allUsersError,
    isLoading: isLoadingAllUsers,
    isFetching: isFetchingAllUsers,
    refetch: refetchAllUsers,
  } = useGetUsersQuery({});
  const {
    currentData: roleUsersResponse,
    error: roleUsersError,
    isLoading: isLoadingRoleUsers,
    isFetching: isFetchingRoleUsers,
    refetch: refetchRoleUsers,
  } = useGetUsersQuery(
    { role: roleFilter },
    {
      skip: roleFilter === "all",
    },
  );

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
  const roleOptions = useMemo(() => {
    const options = createUserRoleOptions(allUsers);
    return [
      { value: "all", label: "الكل" },
      ...options.map((option) => ({
        ...option,
        label: USER_ROLE_TAB_LABELS[option.value] || option.label,
      })),
    ];
  }, [allUsers]);
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

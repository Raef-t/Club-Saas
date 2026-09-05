import { useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import {
  useCreateRoleMutation,
  useDeleteRoleMutation,
  useGetPermissionsQuery,
  useGetRolesQuery,
  useUpdateRoleMutation,
} from "@/lib/api/rolesApi";
import {
  createRoleStats,
  filterRoles,
  getPermissionCollection,
  getPermissionsFromRoles,
  getRoleCollection,
  mergePermissionCatalog,
} from "./roleUtils";

export function useRoles({ initialRoles, initialPermissions } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [editTarget, setEditTarget] = useState(null);

  const {
    currentData: rolesResponse,
    error: rolesError,
    isLoading: isLoadingRoles,
    isFetching: isFetchingRoles,
    refetch: refetchRoles,
  } = useGetRolesQuery();
  const {
    currentData: permissionsResponse,
    error: permissionsError,
    isLoading: isLoadingPermissions,
    isFetching: isFetchingPermissions,
    refetch: refetchPermissions,
  } = useGetPermissionsQuery();
  const [createRole, { isLoading: isCreating }] = useCreateRoleMutation();
  const [updateRole, { isLoading: isUpdating }] = useUpdateRoleMutation();
  const [deleteRole, { isLoading: isDeleting }] = useDeleteRoleMutation();

  const allRoles = useMemo(
    () => getRoleCollection(rolesResponse || initialRoles),
    [initialRoles, rolesResponse],
  );
  const permissions = useMemo(() => {
    const endpointPermissions = getPermissionCollection(permissionsResponse || initialPermissions);
    const rolePermissions = getPermissionsFromRoles(allRoles);

    return mergePermissionCatalog(endpointPermissions, {
      permissions: rolePermissions,
    });
  }, [allRoles, initialPermissions, permissionsResponse]);
  const roles = useMemo(() => filterRoles(allRoles, search), [allRoles, search]);
  const stats = useMemo(() => createRoleStats(allRoles, permissions), [allRoles, permissions]);

  async function handleCreate(name) {
    try {
      await createRole({ name }).unwrap();
      toast.success("تم إنشاء الدور بنجاح");
      setCreateOpen(false);
      return true;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر إنشاء الدور. حاول مرة أخرى."));
      return false;
    }
  }

  async function handleUpdate(data) {
    try {
      await updateRole(data).unwrap();
      toast.success("تم تعديل الدور بنجاح");
      setEditTarget(null);
      return true;
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر تعديل الدور. حاول مرة أخرى."));
      return false;
    }
  }

  async function handleDelete() {
    if (!deleteTarget || deleteTarget.is_protected) return;
    try {
      await deleteRole(deleteTarget.id).unwrap();
      toast.success("تم حذف الدور بنجاح");
      setDeleteTarget(null);
    } catch (error) {
      toast.error(getApiErrorMessage(error, "تعذر حذف الدور. حاول مرة أخرى."));
    }
  }

  function retry() {
    refetchRoles();
    refetchPermissions();
  }

  return {
    search,
    setSearch,
    roles,
    stats,
    permissions,
    totalRoles: allRoles.length,
    isLoading: isLoadingRoles && allRoles.length === 0,
    isRefreshing: isFetchingRoles || isFetchingPermissions,
    errorMessage: rolesError ? getApiErrorMessage(rolesError, "تعذر تحميل قائمة الأدوار.") : "",
    permissionsError: permissionsError
      ? getApiErrorMessage(permissionsError, "تعذر تحميل قائمة صلاحيات النظام.")
      : "",
    isLoadingPermissions,
    retry,
    createOpen,
    setCreateOpen,
    handleCreate,
    isCreating,
    editTarget,
    setEditTarget,
    handleUpdate,
    isUpdating,
    deleteTarget,
    setDeleteTarget,
    handleDelete,
    isDeleting,
  };
}

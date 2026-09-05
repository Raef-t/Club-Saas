"use client";

import { createContext, useContext, useMemo } from "react";
import {
  canAccessPath,
  getFirstAccessiblePath,
  getUserPermissionNames,
  getUserRoleNames,
  hasAllPermissions,
  hasAnyPermission,
  hasPermission,
  isSuperAdmin,
} from "@/lib/permissions";

const PermissionContext = createContext(null);

export function PermissionProvider({ user, children }) {
  const value = useMemo(
    () => ({
      user,
      roles: getUserRoleNames(user),
      permissions: getUserPermissionNames(user),
      isSuperAdmin: isSuperAdmin(user),
      firstAccessiblePath: getFirstAccessiblePath(user),
      can: (permission) => hasPermission(user, permission),
      canAny: (permissionNames) => hasAnyPermission(user, permissionNames),
      canAll: (permissionNames) => hasAllPermissions(user, permissionNames),
      canAccess: (pathname) => canAccessPath(user, pathname),
    }),
    [user],
  );

  return <PermissionContext.Provider value={value}>{children}</PermissionContext.Provider>;
}

export function usePermissions() {
  const context = useContext(PermissionContext);
  if (!context) {
    throw new Error("usePermissions must be used inside PermissionProvider.");
  }
  return context;
}

export function PermissionGate({ permission, any, all, fallback = null, children }) {
  const access = usePermissions();
  const allowed = permission
    ? access.can(permission)
    : any
      ? access.canAny(any)
      : all
        ? access.canAll(all)
        : true;

  return allowed ? children : fallback;
}

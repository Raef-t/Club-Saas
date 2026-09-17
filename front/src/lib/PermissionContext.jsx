"use client";

import { createContext, useContext, useMemo } from "react";
import {
  getFirstAccessiblePath,
  getRouteAccessRule,
  getUserPermissionNames,
  getUserRoleNames,
  isSuperAdmin,
} from "@/lib/permissions";

const PermissionContext = createContext(null);

export function PermissionProvider({ user, children }) {
  const value = useMemo(() => {
    const roles = getUserRoleNames(user);
    const permissions = getUserPermissionNames(user);
    const permissionSet = new Set(permissions);
    const superAdmin = isSuperAdmin(user);
    const can = (permission) => !permission || superAdmin || permissionSet.has(permission);
    const canAny = (permissionNames = []) =>
      superAdmin || permissionNames.some((permission) => permissionSet.has(permission));
    const canAll = (permissionNames = []) =>
      superAdmin || permissionNames.every((permission) => permissionSet.has(permission));
    const canAccess = (pathname) => {
      const rule = getRouteAccessRule(pathname);
      if (!rule || superAdmin) return true;

      return (!rule.all || canAll(rule.all)) && (!rule.any || canAny(rule.any));
    };

    return {
      user,
      roles,
      permissions,
      isSuperAdmin: superAdmin,
      firstAccessiblePath: getFirstAccessiblePath(user),
      can,
      canAny,
      canAll,
      canAccess,
    };
  }, [user]);

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

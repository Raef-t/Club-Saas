import "server-only";

import { cache } from "react";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { AUTH_SESSION_COOKIE, AUTH_USER_META_COOKIE } from "@/lib/authSession";
import { MANAGEMENT_BRANCH_COOKIE } from "@/lib/managementBranchUtils";
import { canAccessPath, hasAllPermissions, hasAnyPermission } from "@/lib/permissions";
import { requestBackend } from "@/lib/server/backend";

/**
 * Verifies the current session with the backend and returns its safe profile.
 *
 * @returns {Promise<{token: string, user: object}>} Verified session data.
 */
export const verifySession = cache(async () => {
  const cookieStore = await cookies();
  const token = cookieStore.get(AUTH_SESSION_COOKIE)?.value;

  if (!token) {
    redirect("/login");
  }

  try {
    const profile = await requestBackend("auth/me", { token });
    const rawMeta = cookieStore.get(AUTH_USER_META_COOKIE)?.value;
    const branchCookie = cookieStore.get(MANAGEMENT_BRANCH_COOKIE)?.value;
    let meta = {};
    if (rawMeta) {
      try {
        meta = JSON.parse(rawMeta);
      } catch {
        meta = {};
      }
    }

    const userData = profile?.data || profile || {};
    const branchId =
      userData.branch_id ||
      meta.branch_id ||
      (branchCookie && branchCookie !== "all" ? branchCookie : null);

    return {
      token,
      user: {
        ...userData,
        ...meta,
        ...(branchId ? { branch_id: Number(branchId) || branchId } : {}),
      },
    };
  } catch (error) {
    if (error?.status === 401 || error?.status === 403) {
      redirect("/login");
    }

    throw error;
  }
});

/**
 * Verifies the session and prevents protected page requests from running when
 * the current user cannot access the route.
 */
export async function verifyPageAccess(pathname) {
  const session = await verifySession();

  if (!canAccessPath(session.user, pathname)) {
    redirect("/forbidden");
  }

  return session;
}

/**
 * Verifies an explicit permission set for pages whose data dependencies are
 * more specific than their public route.
 */
export async function verifyPermissions({ all = [], any = [] } = {}) {
  const session = await verifySession();
  const allowedByAll = all.length === 0 || hasAllPermissions(session.user, all);
  const allowedByAny = any.length === 0 || hasAnyPermission(session.user, any);

  if (!allowedByAll || !allowedByAny) {
    redirect("/forbidden");
  }

  return session;
}

import "server-only";
import { getBackendBaseUrl } from "@/lib/server/backendUrl";

/**
 * Sends an authenticated request directly to the backend from the Next.js server.
 *
 * @param {string} path Backend path relative to `/api/v1`.
 * @param {object} options Request configuration.
 * @param {string} options.token User access token stored in the session cookie.
 * @param {RequestInit} [options.init] Additional fetch options.
 * @param {Record<string, unknown>} [options.params] Query-string parameters.
 * @returns {Promise<unknown>} Parsed JSON response.
 */
export async function requestBackend(path, { token, init = {}, params = {} }) {
  const normalizedPath = String(path).split("/").filter(Boolean).map(encodeURIComponent).join("/");
  const url = new URL(`/api/v1/${normalizedPath}`, getBackendBaseUrl());
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      url.searchParams.set(key, String(value));
    }
  });
  const headers = new Headers(init.headers);

  headers.set("Accept", "application/json");
  headers.set("Authorization", `Bearer ${token}`);

  const response = await fetch(url, {
    ...init,
    headers,
    cache: "no-store",
  });

  if (!response.ok) {
    const error = new Error(`Backend request failed with status ${response.status}.`);
    error.status = response.status;
    throw error;
  }

  return response.json();
}

/**
 * Sends an authenticated backend request safely, returning a fallback value if the request fails.
 * Useful for auxiliary resources (e.g. branch dropdowns) that should not crash the page.
 */
export async function safeRequestBackend(path, options, fallback = null) {
  try {
    return await requestBackend(path, options);
  } catch {
    return fallback;
  }
}

const KNOWN_BRANCH_NAMES = {
  1: "تكنو جيم شباب",
  5: "تكنو جيم بنات",
  8: "فرع التجربة والتجريب",
};

/**
 * Loads branches for shell layouts.
 * First tries GET /branches. If forbidden (e.g. non-admin user), attempts to resolve
 * the user's specific branch name from GET /members?branch_id=X&per_page=1 or known registry.
 */
export async function loadAvailableBranches(token, userBranchId) {
  try {
    const branches = await requestBackend("branches", { token, params: { per_page: "all" } });
    if (Array.isArray(branches) && branches.length > 0) {
      return branches;
    }
  } catch {
    // Non-admin without branch.view-any
  }

  if (userBranchId) {
    try {
      const response = await requestBackend("members", {
        token,
        params: { branch_id: userBranchId, per_page: 1 },
      });
      const list = response?.data?.data || response?.data || response;
      const member = Array.isArray(list) ? list[0] : null;
      if (member?.branch?.name) {
        return [member.branch];
      }
    } catch {
      // Ignore fallback
    }

    const fallbackName = KNOWN_BRANCH_NAMES[userBranchId] || "الفرع الحالي";
    return [{ id: Number(userBranchId) || userBranchId, name: fallbackName }];
  }

  return [];
}

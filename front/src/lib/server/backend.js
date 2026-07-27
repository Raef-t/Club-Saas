import "server-only";

const API_BASE_URL = process.env.API_BASE_URL || "http://issgroup-001-site1.anytempurl.com";

/**
 * Sends an authenticated request directly to the backend from the Next.js server.
 *
 * @param {string} path Backend path relative to `/api/v1`.
 * @param {object} options Request configuration.
 * @param {string} options.token User access token stored in the session cookie.
 * @param {RequestInit} [options.init] Additional fetch options.
 * @returns {Promise<unknown>} Parsed JSON response.
 */
export async function requestBackend(path, { token, init = {} }) {
  const normalizedPath = String(path).split("/").filter(Boolean).map(encodeURIComponent).join("/");
  const url = new URL(`/api/v1/${normalizedPath}`, API_BASE_URL);
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

/**
 * Resolves the private backend URL.
 * HTTP is temporarily accepted until TLS is enabled on the remote backend.
 */
export function resolveBackendBaseUrl(value) {
  if (!value) {
    throw new Error("API_BASE_URL is required.");
  }

  let url;
  try {
    url = new URL(value);
  } catch {
    throw new Error("API_BASE_URL must be a valid absolute URL.");
  }

  if (url.username || url.password) {
    throw new Error("API_BASE_URL must not contain credentials.");
  }

  if (!new Set(["http:", "https:"]).has(url.protocol)) {
    throw new Error("API_BASE_URL must use HTTP or HTTPS.");
  }

  return url;
}

export function getBackendBaseUrl() {
  return resolveBackendBaseUrl(process.env.API_BASE_URL);
}

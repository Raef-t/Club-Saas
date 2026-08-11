/**
 * Resolves the private backend URL.
 * Remote HTTP is temporarily accepted in development, while production keeps
 * enforcing HTTPS so the exception cannot be shipped accidentally.
 */
export function resolveBackendBaseUrl(value, { nodeEnv = process.env.NODE_ENV } = {}) {
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

  const isDevelopmentHttp = nodeEnv !== "production" && url.protocol === "http:";

  if (url.protocol !== "https:" && !isDevelopmentHttp) {
    throw new Error("Remote backend connections must use HTTPS.");
  }

  return url;
}

export function getBackendBaseUrl() {
  return resolveBackendBaseUrl(process.env.API_BASE_URL);
}

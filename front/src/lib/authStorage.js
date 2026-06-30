const AUTH_STORAGE_KEYS = [
  "access_token",
  "token_type",
  "auth_user",
  "auth_session",
];

export function clearAuthStorage() {
  if (typeof window === "undefined") return;

  AUTH_STORAGE_KEYS.forEach((key) => {
    window.localStorage.removeItem(key);
  });
}

export function getAuthHeader() {
  if (typeof window === "undefined") return null;

  const token = window.localStorage.getItem("access_token");
  const tokenType = window.localStorage.getItem("token_type") || "Bearer";

  return token ? `${tokenType} ${token}` : null;
}

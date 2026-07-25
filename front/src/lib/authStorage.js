const AUTH_STORAGE_KEYS = [
  "access_token",
  "token_type",
  "auth_user",
  "auth_session",
  "auth_active",
];

export function clearAuthStorage() {
  if (typeof window === "undefined") return;

  [window.localStorage, window.sessionStorage].forEach((storage) => {
    AUTH_STORAGE_KEYS.forEach((key) => {
      storage.removeItem(key);
    });
  });
}

export function saveAuthStorage(payload, remember) {
  if (typeof window === "undefined") return;

  clearAuthStorage();

  const storage = remember ? window.localStorage : window.sessionStorage;
  const user = payload?.data?.user || {};

  storage.setItem("auth_active", "true");
  storage.setItem("auth_user", JSON.stringify(user));
  storage.setItem(
    "auth_session",
    JSON.stringify({
      authenticated: true,
      user,
    }),
  );
}

export function getAuthHeader() {
  if (typeof window === "undefined") return null;

  const storageWithToken = [window.localStorage, window.sessionStorage].find(
    (storage) => storage.getItem("access_token"),
  );

  if (!storageWithToken) return null;

  const token = storageWithToken.getItem("access_token");
  const tokenType = storageWithToken.getItem("token_type") || "Bearer";

  return token ? `${tokenType} ${token}` : null;
}

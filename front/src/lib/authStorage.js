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

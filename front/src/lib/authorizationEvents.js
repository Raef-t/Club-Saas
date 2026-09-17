export const AUTHORIZATION_DENIED_EVENT = "techno-gym:authorization-denied";

const DEFAULT_FORBIDDEN_MESSAGE = "عذراً، ليس لديك الصلاحية الكافية لإتمام هذا الإجراء.";

/**
 * Normalizes the backend's 403 payload before it crosses into UI code.
 */
export function getAuthorizationDeniedDetails(error) {
  const data = error?.data && typeof error.data === "object" ? error.data : {};

  return {
    message:
      typeof data.message === "string" && data.message.trim()
        ? data.message.trim()
        : DEFAULT_FORBIDDEN_MESSAGE,
    permission:
      typeof data.permission === "string" && data.permission.trim() ? data.permission.trim() : null,
  };
}

/**
 * Lets the Redux transport notify the mounted toast layer without coupling the
 * store to React. No-op during server rendering and tests without a DOM.
 */
export function publishAuthorizationDenied(error) {
  const details = getAuthorizationDeniedDetails(error);

  if (details.permission && typeof console !== "undefined") {
    console.warn(`Missing Permission: ${details.permission}`);
  }

  if (typeof window !== "undefined" && typeof window.dispatchEvent === "function") {
    window.dispatchEvent(
      new CustomEvent(AUTHORIZATION_DENIED_EVENT, {
        detail: details,
      }),
    );
  }

  return details;
}

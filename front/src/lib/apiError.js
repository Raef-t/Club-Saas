const DEFAULT_ERROR_MESSAGE = "تعذر إكمال العملية. حاول مرة أخرى.";

/**
 * Extracts a user-facing message from RTK Query, fetch, and validation errors.
 *
 * @param {unknown} error Error returned by the application or backend.
 * @param {string} [fallback] Message used when the error has no safe message.
 * @returns {string} Normalized user-facing error message.
 */
export function getApiErrorMessage(error, fallback = DEFAULT_ERROR_MESSAGE) {
  if (!error || typeof error !== "object") {
    return fallback;
  }

  const candidates = [error.data?.message, error.error, error.message];

  return candidates.find((message) => typeof message === "string" && message.trim()) || fallback;
}

/**
 * Returns field validation errors using a consistent object shape.
 *
 * @param {unknown} error Error returned by the backend.
 * @returns {Record<string, string>} First validation message for each field.
 */
export function getApiFieldErrors(error) {
  const errors = error?.data?.errors;

  if (!errors || typeof errors !== "object" || Array.isArray(errors)) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages) ? String(messages[0] || "") : String(messages || ""),
    ]),
  );
}

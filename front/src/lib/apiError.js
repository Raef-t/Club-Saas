const DEFAULT_ERROR_MESSAGE = "تعذر إكمال العملية. حاول مرة أخرى.";

const ERROR_TRANSLATIONS = [
  {
    pattern: /subscription\s*plan\s*has\s*reached\s*its\s*maximum\s*capacity/i,
    translation: "هذه الفعالية مسددة (وصلت خطة الاشتراك إلى الحد الأقصى من المشتركين).",
  },
  {
    pattern: /maximum\s*capacity/i,
    translation: "هذه الفعالية مسددة (تم الوصول إلى الحد الأقصى للمشتركين).",
  },
  {
    pattern: /already\s*subscribed/i,
    translation: "هذا المشترك مسجل بالفعل في هذه الخطة.",
  },
  {
    pattern: /unauthorized|unauthenticated/i,
    translation: "انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجدداً.",
  },
];

/**
 * Translates known English backend error messages to user-friendly Arabic text.
 */
function translateErrorMessage(message) {
  if (typeof message !== "string") return null;
  const trimmed = message.replace(/^[.\s]+|[.\s]+$/g, "");
  for (const item of ERROR_TRANSLATIONS) {
    if (item.pattern.test(trimmed)) {
      return item.translation;
    }
  }
  return trimmed || null;
}

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

  const rawCandidates = [
    error.data?.message,
    error.data?.error,
    error.error,
    error.message,
  ];

  for (const candidate of rawCandidates) {
    if (typeof candidate === "string" && candidate.trim()) {
      const translated = translateErrorMessage(candidate);
      if (translated) return translated;
    }
  }

  return fallback;
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

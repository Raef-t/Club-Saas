export const DEFAULT_PASSWORD = "12345678";

export const PASSWORD_HASH_ALGORITHM = "SHA-256";

function getPasswordHashSecretKey() {
  const secretKey = process.env.NEXT_PUBLIC_PASSWORD_HASH_SECRET_KEY;

  if (!secretKey) {
    throw new Error("إعداد مفتاح تشفير كلمة المرور غير موجود.");
  }

  return secretKey;
}

function bytesToHex(bytes) {
  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, "0")).join("");
}

/**
 * Cross-platform password digest contract shared with Flutter and the API:
 * lowercaseHex(SHA-256(UTF-8(secretKey + password))).
 */
export async function hashPassword(password) {
  if (typeof password !== "string") {
    throw new TypeError("يجب أن تكون كلمة المرور نصاً.");
  }

  if (!globalThis.crypto?.subtle) {
    throw new Error("المتصفح لا يدعم تشفير كلمة المرور المطلوب.");
  }

  const input = new TextEncoder().encode(`${getPasswordHashSecretKey()}${password}`);
  const digest = await globalThis.crypto.subtle.digest(PASSWORD_HASH_ALGORITHM, input);

  return bytesToHex(new Uint8Array(digest));
}

export function hashDefaultPassword() {
  return hashPassword(DEFAULT_PASSWORD);
}

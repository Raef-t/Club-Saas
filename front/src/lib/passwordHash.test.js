import { afterEach, describe, expect, it, vi } from "vitest";
import {
  DEFAULT_PASSWORD,
  PASSWORD_HASH_ALGORITHM,
  hashDefaultPassword,
  hashPassword,
} from "./passwordHash";

describe("password hashing contract", () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it("uses the shared SHA-256 UTF-8 lowercase-hex contract", async () => {
    vi.stubEnv("NEXT_PUBLIC_PASSWORD_HASH_SECRET_KEY", "oid900=rjfreipwhefdk");

    await expect(hashPassword("123456789")).resolves.toBe(
      "f0b2114faf6aae86f7ff35dc67a616d26e46ad97361ab1f4514f4339e5652abd",
    );
    expect(PASSWORD_HASH_ALGORITHM).toBe("SHA-256");
  });

  it("generates the agreed default-password hash", async () => {
    vi.stubEnv("NEXT_PUBLIC_PASSWORD_HASH_SECRET_KEY", "oid900=rjfreipwhefdk");

    expect(DEFAULT_PASSWORD).toBe("12345678");
    await expect(hashDefaultPassword()).resolves.toBe(
      "119f6226667c1bc87396838134392ef4f4d38e68f1719aed7b2dff13be62d5ed",
    );
  });

  it("fails closed when the shared key is missing", async () => {
    vi.stubEnv("NEXT_PUBLIC_PASSWORD_HASH_SECRET_KEY", "");

    await expect(hashPassword("12345678")).rejects.toThrow(
      "إعداد مفتاح تشفير كلمة المرور غير موجود.",
    );
  });
});

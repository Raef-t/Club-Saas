// @vitest-environment node

import { describe, expect, it } from "vitest";
import { resolveBackendBaseUrl } from "./backendUrl";

describe("backend URL transport policy", () => {
  it("accepts HTTPS backends", () => {
    expect(resolveBackendBaseUrl("https://api.example.com", { nodeEnv: "production" }).origin).toBe(
      "https://api.example.com",
    );
  });

  it("temporarily accepts remote HTTP backends in development only", () => {
    expect(resolveBackendBaseUrl("http://203.0.113.10", { nodeEnv: "development" }).origin).toBe(
      "http://203.0.113.10",
    );
    expect(() =>
      resolveBackendBaseUrl("http://api.example.com", { nodeEnv: "production" }),
    ).toThrow("must use HTTPS");
  });

  it("allows loopback HTTP only outside production", () => {
    expect(resolveBackendBaseUrl("http://localhost:8000", { nodeEnv: "development" }).port).toBe(
      "8000",
    );
    expect(() => resolveBackendBaseUrl("http://localhost:8000", { nodeEnv: "production" })).toThrow(
      "must use HTTPS",
    );
  });

  it("rejects URLs containing credentials", () => {
    expect(() =>
      resolveBackendBaseUrl("https://user:secret@api.example.com", { nodeEnv: "production" }),
    ).toThrow("must not contain credentials");
  });
});

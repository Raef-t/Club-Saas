// @vitest-environment node

import { describe, expect, it } from "vitest";
import { resolveBackendBaseUrl } from "./backendUrl";

describe("backend URL transport policy", () => {
  it("accepts HTTPS backends", () => {
    expect(resolveBackendBaseUrl("https://api.example.com").origin).toBe("https://api.example.com");
  });

  it("temporarily accepts remote HTTP backends", () => {
    expect(resolveBackendBaseUrl("http://203.0.113.10").origin).toBe("http://203.0.113.10");
  });

  it("rejects non-HTTP protocols", () => {
    expect(() => resolveBackendBaseUrl("ftp://api.example.com")).toThrow("must use HTTP or HTTPS");
  });

  it("rejects URLs containing credentials", () => {
    expect(() =>
      resolveBackendBaseUrl("https://user:secret@api.example.com", { nodeEnv: "production" }),
    ).toThrow("must not contain credentials");
  });
});

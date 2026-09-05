// @vitest-environment node

import { describe, expect, it } from "vitest";
import { NextRequest } from "next/server";
import { proxy } from "./proxy";

function createRequest(pathname, cookie = "") {
  return new NextRequest(`http://localhost${pathname}`, {
    headers: cookie ? { Cookie: cookie } : undefined,
  });
}

describe("account setup proxy", () => {
  it("redirects an authenticated pending account to account setup", () => {
    const response = proxy(
      createRequest(
        "/management",
        "techno_gym_session=test-token; techno_gym_account_setup=required",
      ),
    );

    expect(response.status).toBe(307);
    expect(response.headers.get("location")).toBe("http://localhost/account-setup");
  });

  it("allows a pending account to open account setup", () => {
    const response = proxy(
      createRequest(
        "/account-setup",
        "techno_gym_session=test-token; techno_gym_account_setup=required",
      ),
    );

    expect(response.headers.get("x-middleware-next")).toBe("1");
  });

  it("redirects setup to login when the session is missing", () => {
    const response = proxy(createRequest("/account-setup"));

    expect(response.status).toBe(307);
    expect(response.headers.get("location")).toContain("/login?next=%2Faccount-setup");
  });

  it("redirects completed accounts away from setup", () => {
    const response = proxy(createRequest("/account-setup", "techno_gym_session=test-token"));

    expect(response.status).toBe(307);
    expect(response.headers.get("location")).toBe("http://localhost/");
  });
});

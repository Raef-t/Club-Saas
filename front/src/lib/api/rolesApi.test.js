import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { rolesApi } from "./rolesApi";

describe("roles API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("creates a role with its technical name, Arabic name, and visibility", async () => {
    let request;
    const NativeRequest = globalThis.Request;
    vi.stubGlobal(
      "Request",
      class extends NativeRequest {
        constructor(input, init) {
          super(typeof input === "string" ? new URL(input, "http://localhost") : input, init);
        }
      },
    );
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input) => {
        request = input;
        return Response.json({ status: "success" });
      }),
    );

    const store = configureStore({
      reducer: { [rolesApi.reducerPath]: rolesApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(rolesApi.middleware),
    });
    const body = {
      name: "reception_supervisor",
      name_ar: "مشرف الاستقبال",
      is_visible: true,
    };

    await store.dispatch(rolesApi.endpoints.createRole.initiate(body)).unwrap();

    expect(request.method).toBe("POST");
    expect(new URL(request.url).pathname).toBe("/api/backend/roles");
    await expect(request.clone().json()).resolves.toEqual(body);
  });

  it("syncs the complete permission-name array for a role", async () => {
    let request;
    const NativeRequest = globalThis.Request;
    vi.stubGlobal(
      "Request",
      class extends NativeRequest {
        constructor(input, init) {
          super(typeof input === "string" ? new URL(input, "http://localhost") : input, init);
        }
      },
    );
    vi.stubGlobal(
      "fetch",
      vi.fn(async (input) => {
        request = input;
        return Response.json({ status: "success" });
      }),
    );

    const store = configureStore({
      reducer: { [rolesApi.reducerPath]: rolesApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(rolesApi.middleware),
    });
    const permissions = ["member.view-any", "member.create"];

    await store
      .dispatch(rolesApi.endpoints.syncRolePermissions.initiate({ id: 9, permissions }))
      .unwrap();

    expect(request.method).toBe("PUT");
    expect(new URL(request.url).pathname).toBe("/api/backend/roles/9/permissions");
    await expect(request.clone().json()).resolves.toEqual({ permissions });
  });
});

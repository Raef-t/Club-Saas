import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { usersApi } from "./usersApi";

describe("user roles API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("assigns and revokes one role using the documented request body", async () => {
    const requests = [];
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
        requests.push(input);
        return Response.json({ status: "success" });
      }),
    );

    const store = configureStore({
      reducer: { [usersApi.reducerPath]: usersApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(usersApi.middleware),
    });

    await store
      .dispatch(
        usersApi.endpoints.assignUserRole.initiate({
          userId: 17,
          role: "reception_supervisor",
        }),
      )
      .unwrap();
    await store
      .dispatch(
        usersApi.endpoints.revokeUserRole.initiate({
          userId: 17,
          role: "reception_supervisor",
        }),
      )
      .unwrap();

    expect(requests).toHaveLength(2);
    expect(requests[0].method).toBe("POST");
    expect(requests[1].method).toBe("DELETE");
    expect(new URL(requests[0].url).pathname).toBe("/api/backend/users/17/roles");
    await expect(requests[0].clone().json()).resolves.toEqual({
      role: "reception_supervisor",
    });
    await expect(requests[1].clone().json()).resolves.toEqual({
      role: "reception_supervisor",
    });
  });
});

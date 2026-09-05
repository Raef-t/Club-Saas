import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { staffApi } from "./staffApi";

describe("staff API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("sends the required confirmation when deleting a staff member", async () => {
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
        return new Response(JSON.stringify({ status: "success" }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [staffApi.reducerPath]: staffApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(staffApi.middleware),
    });

    await store
      .dispatch(
        staffApi.endpoints.deleteStaffMember.initiate({ id: 23, confirmation: "delete" }),
      )
      .unwrap();

    const url = new URL(request.url);
    expect(request.method).toBe("DELETE");
    expect(url.pathname).toBe("/api/backend/staff/23");
    expect(url.searchParams.get("confirmation")).toBe("delete");
  });
});

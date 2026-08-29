import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { branchesApi } from "./branchesApi";

describe("branches API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("sends the required confirmation when deleting a branch", async () => {
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
      reducer: { [branchesApi.reducerPath]: branchesApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(branchesApi.middleware),
    });

    await store
      .dispatch(branchesApi.endpoints.deleteBranch.initiate({ id: 17, confirmation: "delete" }))
      .unwrap();

    const url = new URL(request.url);
    expect(request.method).toBe("DELETE");
    expect(url.pathname).toBe("/api/backend/branches/17");
    expect(url.searchParams.get("confirmation")).toBe("delete");
  });
});

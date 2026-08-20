import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { playerSubscriptionsApi } from "./playerSubscriptionsApi";

describe("playerSubscriptions API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("sends delete request with is_refunded query param and body when provided", async () => {
    let capturedRequest;
    let capturedBody;
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
      vi.fn(async (input, init) => {
        capturedRequest = input instanceof Request ? input : new Request(input, init);
        if (capturedRequest.body) {
          const cloned = capturedRequest.clone();
          capturedBody = await cloned.json().catch(() => null);
        } else if (init?.body) {
          capturedBody = typeof init.body === "string" ? JSON.parse(init.body) : init.body;
        }
        return new Response(JSON.stringify({ status: "success", message: "Deleted successfully" }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [playerSubscriptionsApi.reducerPath]: playerSubscriptionsApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(playerSubscriptionsApi.middleware),
    });

    await store
      .dispatch(
        playerSubscriptionsApi.endpoints.deletePlayerSubscription.initiate({
          id: 9999,
          is_refunded: true,
          reason: "طلب اللاعب إلغاء واسترداد المبلغ",
        }),
      )
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(capturedRequest.method).toBe("DELETE");
    expect(url.pathname).toBe("/api/backend/player-subscriptions/9999");
    expect(url.searchParams.get("is_refunded")).toBe("true");
    expect(capturedBody).toEqual({
      is_refunded: true,
      reason: "طلب اللاعب إلغاء واسترداد المبلغ",
    });
  });

  it("sends delete request with is_refunded=false when unrefunded", async () => {
    let capturedRequest;
    let capturedBody;
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
      vi.fn(async (input, init) => {
        capturedRequest = input instanceof Request ? input : new Request(input, init);
        if (capturedRequest.body) {
          const cloned = capturedRequest.clone();
          capturedBody = await cloned.json().catch(() => null);
        } else if (init?.body) {
          capturedBody = typeof init.body === "string" ? JSON.parse(init.body) : init.body;
        }
        return new Response(JSON.stringify({ status: "success" }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [playerSubscriptionsApi.reducerPath]: playerSubscriptionsApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(playerSubscriptionsApi.middleware),
    });

    await store
      .dispatch(
        playerSubscriptionsApi.endpoints.deletePlayerSubscription.initiate({
          id: 1234,
          is_refunded: false,
        }),
      )
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(capturedRequest.method).toBe("DELETE");
    expect(url.pathname).toBe("/api/backend/player-subscriptions/1234");
    expect(url.searchParams.get("is_refunded")).toBe("false");
    expect(capturedBody).toEqual({
      is_refunded: false,
    });
  });

  it("supports backwards compatible delete with primitive id", async () => {
    let capturedRequest;
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
      vi.fn(async (input, init) => {
        capturedRequest = input instanceof Request ? input : new Request(input, init);
        return new Response(JSON.stringify({ status: "success" }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [playerSubscriptionsApi.reducerPath]: playerSubscriptionsApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(playerSubscriptionsApi.middleware),
    });

    await store
      .dispatch(playerSubscriptionsApi.endpoints.deletePlayerSubscription.initiate(5678))
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(capturedRequest.method).toBe("DELETE");
    expect(url.pathname).toBe("/api/backend/player-subscriptions/5678");
    expect(url.searchParams.get("is_refunded")).toBeNull();
  });
});

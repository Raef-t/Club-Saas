import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { notificationsApi } from "./notificationsApi";

describe("notifications API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("marks the notification reception as read", async () => {
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
        return new Response(JSON.stringify({ status: true }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [notificationsApi.reducerPath]: notificationsApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(notificationsApi.middleware),
    });

    await store.dispatch(notificationsApi.endpoints.markNotificationRead.initiate(399)).unwrap();

    expect(request.method).toBe("PATCH");
    expect(new URL(request.url).pathname).toBe("/api/backend/notifications/399/read");
  });
});

import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { lockersApi } from "./lockersApi";

describe("lockers API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("sends the early-release reason in the DELETE request body", async () => {
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
      reducer: { [lockersApi.reducerPath]: lockersApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(lockersApi.middleware),
    });

    await store
      .dispatch(
        lockersApi.endpoints.releaseLockerReservation.initiate({
          id: 42,
          body: { reason: "طلب اللاعب فك الحجز مبكرًا" },
        }),
      )
      .unwrap();

    expect(request.method).toBe("DELETE");
    expect(new URL(request.url).pathname).toBe("/api/backend/lockers/42/reservations/current");
    expect(await request.json()).toEqual({ reason: "طلب اللاعب فك الحجز مبكرًا" });
  });
});

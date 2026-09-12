import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { attendanceApi } from "./attendanceApi";

describe("attendance API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("checks a member in and deducts sessions with one reception request", async () => {
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
      reducer: { [attendanceApi.reducerPath]: attendanceApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(attendanceApi.middleware),
    });
    const body = {
      member_id: 12,
      branch_id: 3,
      player_subscription_ids: [108],
      notes: "حضور خارج الموعد",
    };

    await store.dispatch(attendanceApi.endpoints.checkInAndDeduct.initiate(body)).unwrap();

    expect(request.method).toBe("POST");
    expect(new URL(request.url).pathname).toBe("/api/backend/reception/check-in-and-deduct");
    expect(await request.json()).toEqual(body);
  });
});

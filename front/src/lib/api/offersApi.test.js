import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { offersApi } from "./offersApi";

describe("offers API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("sends branch_id, is_active, and available_only query parameters to getOffers", async () => {
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
        return new Response(JSON.stringify({ data: [] }), {
          status: 200,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [offersApi.reducerPath]: offersApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(offersApi.middleware),
    });

    await store
      .dispatch(
        offersApi.endpoints.getOffers.initiate({
          branch_id: 2,
          is_active: true,
          available_only: true,
        }),
      )
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(url.pathname).toBe("/api/backend/offers");
    expect(Object.fromEntries(url.searchParams)).toEqual({
      branch_id: "2",
      is_active: "true",
      available_only: "true",
    });
  });

  it("sends payload to subscribeToOffer mutation", async () => {
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
        capturedBody = await capturedRequest.clone().json();
        return new Response(JSON.stringify({ status: "success", data: {} }), {
          status: 201,
          headers: { "Content-Type": "application/json" },
        });
      }),
    );

    const store = configureStore({
      reducer: { [offersApi.reducerPath]: offersApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(offersApi.middleware),
    });

    await store
      .dispatch(
        offersApi.endpoints.subscribeToOffer.initiate({
          id: 5,
          body: {
            member_id: 12,
            paid_amount: 1500,
            payment_method: "cash",
            receipt_number: "REC-99",
          },
        }),
      )
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(url.pathname).toBe("/api/backend/offers/5/subscribe");
    expect(capturedRequest.method).toBe("POST");
    expect(capturedBody).toEqual({
      member_id: 12,
      paid_amount: 1500,
      payment_method: "cash",
      receipt_number: "REC-99",
    });
  });

  it("sends confirm parameter when deleting an offer", async () => {
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
      reducer: { [offersApi.reducerPath]: offersApi.reducer },
      middleware: (getDefaultMiddleware) =>
        getDefaultMiddleware().concat(offersApi.middleware),
    });

    await store
      .dispatch(
        offersApi.endpoints.deleteOffer.initiate({
          id: 7,
          confirm: "delete",
        }),
      )
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(url.pathname).toBe("/api/backend/offers/7");
    expect(url.searchParams.get("confirm")).toBe("delete");
    expect(capturedRequest.method).toBe("DELETE");
  });
});

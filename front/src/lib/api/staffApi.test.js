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
      .dispatch(staffApi.endpoints.deleteStaffMember.initiate({ id: 23, confirmation: "delete" }))
      .unwrap();

    const url = new URL(request.url);
    expect(request.method).toBe("DELETE");
    expect(url.pathname).toBe("/api/backend/staff/23");
    expect(url.searchParams.get("confirmation")).toBe("delete");
  });

  it("posts multipart form data to the staff photo endpoint", async () => {
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
    const body = new FormData();
    body.append("photo", new File(["image"], "staff.jpg", { type: "image/jpeg" }));

    await store.dispatch(staffApi.endpoints.updateStaffPhoto.initiate({ id: 1, body })).unwrap();

    const url = new URL(request.url);
    expect(request.method).toBe("POST");
    expect(url.pathname).toBe("/api/backend/staff/1/photo");
    expect(request.headers.get("content-type")).toContain("multipart/form-data; boundary=");
  });

  it("sends staff PUT updates as JSON", async () => {
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
    const body = {
      first_name: "ريم",
      country_code: "+963",
      phone_number: "0991234567",
      branch_ids: [2],
    };

    await store.dispatch(staffApi.endpoints.updateStaffMember.initiate({ id: 4, body })).unwrap();

    expect(request.method).toBe("PUT");
    expect(new URL(request.url).pathname).toBe("/api/backend/staff/4");
    expect(request.headers.get("content-type")).toContain("application/json");
    await expect(request.clone().json()).resolves.toEqual(body);
  });
});

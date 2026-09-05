import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { authApi, createProfileUpdateBody } from "./authApi";

describe("profile update body", () => {
  it("keeps only fields supported by the profile endpoint", () => {
    const body = createProfileUpdateBody(
      {
        dob: "1998-05-15",
        address: "دمشق - المزة",
        how_did_you_hear: "عن طريق صديق",
      },
      {
        first_name: " أحمد ",
        last_name: " محمد ",
        phone_number: " 0991234567 ",
        gender: "male",
        country_code: "+963",
        reason: "تصحيح البيانات",
      },
    );

    expect(body).toEqual({
      first_name: "أحمد",
      last_name: "محمد",
      phone_number: "0991234567",
      dob: "1998-05-15",
      gender: "male",
      address: "دمشق - المزة",
      how_did_you_hear: "عن طريق صديق",
    });
  });
});

describe("auth profile API", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("updates the current profile through PUT /auth/profile", async () => {
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
      reducer: { [authApi.reducerPath]: authApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(authApi.middleware),
    });
    const body = {
      first_name: "أحمد",
      last_name: "محمد",
      phone_number: "0991234567",
      gender: "male",
    };

    await store.dispatch(authApi.endpoints.updateProfile.initiate(body)).unwrap();

    expect(request.method).toBe("PUT");
    expect(new URL(request.url).pathname).toBe("/api/backend/auth/profile");
    await expect(request.clone().json()).resolves.toEqual(body);
  });
});

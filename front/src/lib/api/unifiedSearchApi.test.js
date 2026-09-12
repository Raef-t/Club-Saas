import { configureStore } from "@reduxjs/toolkit";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { activitiesApi } from "./activitiesApi";
import { coachesApi } from "./coachesApi";
import { membersApi } from "./membersApi";
import { playerSubscriptionsApi } from "./playerSubscriptionsApi";
import { staffApi } from "./staffApi";
import { subscriptionPlansApi } from "./subscriptionPlansApi";
import { usersApi } from "./usersApi";

const SEARCH_CASES = [
  [membersApi, "getMembers", "/api/backend/members"],
  [subscriptionPlansApi, "getSubscriptionPlans", "/api/backend/subscription-plans"],
  [playerSubscriptionsApi, "getPlayerSubscriptions", "/api/backend/player-subscriptions"],
  [activitiesApi, "getActivities", "/api/backend/activities"],
  [activitiesApi, "getActivityTypes", "/api/backend/activity-types"],
  [staffApi, "getStaff", "/api/backend/staff"],
  [coachesApi, "getCoaches", "/api/backend/coaches"],
  [usersApi, "getUsers", "/api/backend/users"],
];

describe("unified list search API", () => {
  let capturedRequest;

  beforeEach(() => {
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
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it.each(SEARCH_CASES)("sends only search text to %s.%s", async (api, endpoint, pathname) => {
    const store = configureStore({
      reducer: { [api.reducerPath]: api.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(api.middleware),
    });

    await store.dispatch(api.endpoints[endpoint].initiate({ search: "طارق 123" })).unwrap();

    const url = new URL(capturedRequest.url);
    expect(url.pathname).toBe(pathname);
    expect(Object.fromEntries(url.searchParams)).toEqual({ search: "طارق 123" });
  });

  it("sends the supported coach employment type beside search", async () => {
    const store = configureStore({
      reducer: { [coachesApi.reducerPath]: coachesApi.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(coachesApi.middleware),
    });

    await store
      .dispatch(
        coachesApi.endpoints.getCoaches.initiate({
          search: "ماهر",
          employment_type: "hybrid",
        }),
      )
      .unwrap();

    const url = new URL(capturedRequest.url);
    expect(Object.fromEntries(url.searchParams)).toEqual({
      search: "ماهر",
      employment_type: "hybrid",
    });
  });
});

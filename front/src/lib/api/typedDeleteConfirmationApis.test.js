import { configureStore } from "@reduxjs/toolkit";
import { afterEach, describe, expect, it, vi } from "vitest";
import { accountingApi } from "./accountingApi";
import { activitiesApi } from "./activitiesApi";
import { branchesApi } from "./branchesApi";
import { clubsApi } from "./clubsApi";
import { coachesApi } from "./coachesApi";
import { lockersApi } from "./lockersApi";
import { membersApi } from "./membersApi";
import { playerSubscriptionsApi } from "./playerSubscriptionsApi";
import { staffApi } from "./staffApi";
import { subscriptionPlansApi } from "./subscriptionPlansApi";

const DELETE_CONFIRMATION = "delete";

const typedDeleteCases = [
  {
    name: "activity",
    api: activitiesApi,
    endpoint: "deleteActivity",
    arg: { id: 11, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/activities/11",
  },
  {
    name: "branch",
    api: branchesApi,
    endpoint: "deleteBranch",
    arg: { id: 12, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/branches/12",
  },
  {
    name: "branch shift",
    api: branchesApi,
    endpoint: "deleteBranchShift",
    arg: { branchId: 12, shiftId: 13, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/branches/12/shifts/13",
  },
  {
    name: "branch holiday",
    api: branchesApi,
    endpoint: "deleteBranchHoliday",
    arg: { branchId: 12, holidayId: 14, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/holidays/14",
  },
  {
    name: "club",
    api: clubsApi,
    endpoint: "deleteClub",
    arg: { id: 15, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/clubs/15",
  },
  {
    name: "coach",
    api: coachesApi,
    endpoint: "deleteCoach",
    arg: { id: 16, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/coaches/16",
  },
  {
    name: "locker",
    api: lockersApi,
    endpoint: "deleteLocker",
    arg: { id: 17, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/lockers/17",
  },
  {
    name: "member",
    api: membersApi,
    endpoint: "deleteMember",
    arg: { id: 18, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/members/18",
  },
  {
    name: "staff member",
    api: staffApi,
    endpoint: "deleteStaffMember",
    arg: { id: 19, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/staff/19",
  },
  {
    name: "subscription plan",
    api: subscriptionPlansApi,
    endpoint: "deleteSubscriptionPlan",
    arg: { id: 20, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/subscription-plans/20",
  },
  {
    name: "player subscription",
    api: playerSubscriptionsApi,
    endpoint: "deletePlayerSubscription",
    arg: { id: 21, confirmation: DELETE_CONFIRMATION, is_refunded: false },
    pathname: "/api/backend/player-subscriptions/21",
  },
  {
    name: "partner",
    api: accountingApi,
    endpoint: "deletePartner",
    arg: { id: 22, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/accounting/partners/22",
  },
  {
    name: "salary payment",
    api: accountingApi,
    endpoint: "deleteSalaryPayment",
    arg: { id: 23, confirmation: DELETE_CONFIRMATION },
    pathname: "/api/backend/accounting/salary-payments/23",
  },
];

describe("typed delete confirmation APIs", () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it.each(typedDeleteCases)("sends confirmation when deleting $name", async (testCase) => {
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

    const { api, endpoint, arg, pathname } = testCase;
    const store = configureStore({
      reducer: { [api.reducerPath]: api.reducer },
      middleware: (getDefaultMiddleware) => getDefaultMiddleware().concat(api.middleware),
    });

    await store.dispatch(api.endpoints[endpoint].initiate(arg)).unwrap();

    const url = new URL(request.url);
    expect(request.method).toBe("DELETE");
    expect(url.pathname).toBe(pathname);
    expect(url.searchParams.get("confirmation")).toBe(DELETE_CONFIRMATION);
  });
});

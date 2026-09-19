import { renderHook, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCreateSubscription } from "./useCreateSubscription";

const { getPlansQuery } = vi.hoisted(() => ({
  getPlansQuery: vi.fn(),
}));

vi.mock("@/lib/api/playerSubscriptionsApi", () => ({
  useCreatePlayerSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
  useUpdatePlayerSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
  useGetPlayerSubscriptionQuery: () => ({
    data: undefined,
    error: undefined,
    isLoading: false,
    isFetching: false,
    refetch: vi.fn(),
  }),
}));

vi.mock("@/lib/api/membersApi", () => ({
  useGetMembersQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/subscriptionPlansApi", () => ({
  useGetSubscriptionPlansQuery: (params) => getPlansQuery(params),
}));

vi.mock("@/lib/api/activitiesApi", () => ({
  useGetActivityTypesQuery: () => ({
    currentData: {
      data: [
        { id: 2, code: "private_training", name: "تدريب خاص" },
        { id: 1, code: "general_training", name: "تدريب عام" },
      ],
    },
    error: undefined,
    isLoading: false,
    isFetching: false,
  }),
  useGetActivitiesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/coachesApi", () => ({
  useGetCoachesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/offersApi", () => ({
  useGetOffersQuery: () => ({ data: { data: [] }, isLoading: false, isFetching: false }),
  useSubscribeToOfferMutation: () => [vi.fn(), { isLoading: false }],
}));

vi.mock("@/components/ui/Toast", () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn() }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "all" }),
}));

describe("create subscription defaults", () => {
  beforeEach(() => {
    getPlansQuery.mockReset();
    getPlansQuery.mockReturnValue({
      currentData: { data: [] },
      error: undefined,
      isLoading: false,
      isFetching: false,
    });
  });

  it("selects the first business activity type instead of an all option", async () => {
    const { result } = renderHook(() => useCreateSubscription());

    await waitFor(() => expect(result.current.selectedActivityTypeId).toBe("1"));
    expect(getPlansQuery.mock.calls.at(-1)[0]).toMatchObject({
      activity_type_id: "1",
      available: true,
    });
  });
});

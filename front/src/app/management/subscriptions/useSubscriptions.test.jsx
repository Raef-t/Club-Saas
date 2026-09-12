import { act, renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useSubscriptions } from "./useSubscriptions";

const { getPlayerSubscriptionsQuery } = vi.hoisted(() => ({
  getPlayerSubscriptionsQuery: vi.fn(),
}));

vi.mock("next/navigation", () => ({
  useSearchParams: () => ({ get: () => null }),
}));

vi.mock("@/lib/api/playerSubscriptionsApi", () => ({
  useGetPlayerSubscriptionsQuery: (params) => getPlayerSubscriptionsQuery(params),
  useGetPlayerSubscriptionQuery: () => ({
    data: undefined,
    error: undefined,
    isFetching: false,
    isLoading: false,
    refetch: vi.fn(),
  }),
  useFreezeSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
  useUnfreezeSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
  useCancelSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
  useRenewSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
  useDeletePlayerSubscriptionMutation: () => [vi.fn(), { isLoading: false }],
}));

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/activitiesApi", () => ({
  useGetActivityTypesQuery: () => ({
    data: {
      data: [
        { id: 1, name: "تدريب عام" },
        { id: 2, name: "تدريب خاص" },
        { id: 3, name: "حصة جماعية" },
      ],
    },
    isLoading: false,
    isFetching: false,
  }),
}));

vi.mock("@/lib/api/subscriptionPlansApi", () => ({
  useGetSubscriptionPlansQuery: () => ({
    data: { data: [] },
    error: undefined,
    isLoading: false,
    isFetching: false,
  }),
}));

vi.mock("@/components/ui/Toast", () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn() }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({
    selectedBranchId: "all",
    setSelectedBranchId: vi.fn(),
  }),
}));

describe("subscription activity type filter", () => {
  beforeEach(() => {
    getPlayerSubscriptionsQuery.mockReset();
    getPlayerSubscriptionsQuery.mockReturnValue({
      currentData: { data: [], meta: { total: 0 } },
      error: undefined,
      isFetching: false,
      isLoading: false,
      refetch: vi.fn(),
    });
  });

  it("requests subscriptions with activity_type_id after choosing an activity type", () => {
    const { result } = renderHook(() => useSubscriptions());

    expect(getPlayerSubscriptionsQuery.mock.calls.at(-1)[0]).not.toHaveProperty("activity_type_id");
    expect(result.current.activityTypes).toHaveLength(3);

    act(() => result.current.setActivityTypeId("2"));

    expect(getPlayerSubscriptionsQuery.mock.calls.at(-1)[0]).toMatchObject({
      activity_type_id: "2",
      page: 1,
    });
  });
});

import { act, renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useSubscriptionPlans } from "./useSubscriptionPlans";

const { getSubscriptionPlansQuery } = vi.hoisted(() => ({
  getSubscriptionPlansQuery: vi.fn(),
}));

vi.mock("next/navigation", () => ({
  useSearchParams: () => ({ get: () => null }),
}));

vi.mock("@/lib/api/subscriptionPlansApi", () => ({
  useGetSubscriptionPlansQuery: (params) => getSubscriptionPlansQuery(params),
  useGetSubscriptionPlanQuery: () => ({
    data: undefined,
    error: undefined,
    isFetching: false,
    isLoading: false,
  }),
  useGetSubscriptionPlanPlayersQuery: () => ({
    data: undefined,
    error: undefined,
    isFetching: false,
    isLoading: false,
    refetch: vi.fn(),
  }),
  useCreateSubscriptionPlanMutation: () => [vi.fn(), { isLoading: false }],
  useUpdateSubscriptionPlanMutation: () => [vi.fn(), { isLoading: false }],
  useDeleteSubscriptionPlanMutation: () => [vi.fn(), { isLoading: false }],
  useSuspendSubscriptionPlanMutation: () => [vi.fn(), { isLoading: false }],
  useResumeSubscriptionPlanMutation: () => [vi.fn(), { isLoading: false }],
}));

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/activitiesApi", () => ({
  useGetActivitiesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/coachesApi", () => ({
  useGetCoachesQuery: () => ({ data: { data: [] } }),
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

describe("useSubscriptionPlans", () => {
  const mockStats = {
    total_plans: 25,
    active_plans: 18,
    inactive_plans: 7,
    max_sessions_per_week: 5,
  };

  const mockPlans = [
    { id: 1, name: { ar: "خطة تجريبية نشطة" }, status: "active", is_active: true },
    { id: 2, name: { ar: "خطة تجريبية غير نشطة" }, status: "inactive", is_active: false },
  ];

  beforeEach(() => {
    getSubscriptionPlansQuery.mockReset();
    getSubscriptionPlansQuery.mockReturnValue({
      currentData: {
        data: mockPlans,
        stats: mockStats,
        meta: { total: 25, per_page: 15, current_page: 1, last_page: 2 },
      },
      error: undefined,
      isFetching: false,
      isLoading: false,
      refetch: vi.fn(),
    });
  });

  it("renders stat cards using API stats and includes the red inactive plans card instead of average price", () => {
    const { result } = renderHook(() => useSubscriptionPlans());

    expect(result.current.stats).toHaveLength(4);

    // Card 1: إجمالي الخطط
    expect(result.current.stats[0]).toMatchObject({
      title: "إجمالي الخطط",
      value: (25).toLocaleString("ar"),
      tone: "yellow",
      active: true,
    });

    // Card 2: الخطط الفعالة
    expect(result.current.stats[1]).toMatchObject({
      title: "الخطط الفعالة",
      value: (18).toLocaleString("ar"),
      tone: "green",
      active: false,
    });

    // Card 3: الخطط غير النشطة (replaces متوسط السعر)
    expect(result.current.stats[2]).toMatchObject({
      title: "الخطط غير النشطة",
      value: (7).toLocaleString("ar"),
      tone: "red",
      active: false,
    });

    // Card 4: أكثر جلسات أسبوعياً
    expect(result.current.stats[3]).toMatchObject({
      title: "أكثر جلسات أسبوعياً",
      value: `${(5).toLocaleString("ar")} جلسة`,
      tone: "purple",
    });
  });

  it("sends status=inactive, page=1, per_page=15 to API when clicking inactive plans card without per_page=all", () => {
    const { result } = renderHook(() => useSubscriptionPlans());

    // Initial query should not have status
    expect(getSubscriptionPlansQuery.mock.calls.at(-1)[0]).toEqual({
      page: 1,
      per_page: 15,
    });

    // Click "الخطط غير النشطة"
    act(() => {
      result.current.stats[2].onClick();
    });

    // Query sent to API must be server-side filtered
    const lastCallParams = getSubscriptionPlansQuery.mock.calls.at(-1)[0];
    expect(lastCallParams).toEqual({
      status: "inactive",
      page: 1,
      per_page: 15,
    });
    expect(lastCallParams.per_page).not.toBe("all");
    expect(result.current.stats[2].active).toBe(true);
    expect(result.current.stats[0].active).toBe(false);
  });

  it("sends status=active to API when clicking active plans card", () => {
    const { result } = renderHook(() => useSubscriptionPlans());

    act(() => {
      result.current.stats[1].onClick();
    });

    expect(getSubscriptionPlansQuery.mock.calls.at(-1)[0]).toEqual({
      status: "active",
      page: 1,
      per_page: 15,
    });
    expect(result.current.stats[1].active).toBe(true);
  });

  it("omits status parameter when clicking total plans card", () => {
    const { result } = renderHook(() => useSubscriptionPlans());

    // Switch to inactive first
    act(() => {
      result.current.stats[2].onClick();
    });
    expect(getSubscriptionPlansQuery.mock.calls.at(-1)[0].status).toBe("inactive");

    // Click total plans card
    act(() => {
      result.current.stats[0].onClick();
    });

    const finalCallParams = getSubscriptionPlansQuery.mock.calls.at(-1)[0];
    expect(finalCallParams).toEqual({
      page: 1,
      per_page: 15,
    });
    expect(finalCallParams).not.toHaveProperty("status");
    expect(result.current.stats[0].active).toBe(true);
  });

  it("does not filter plans locally on the client and delegates pagination directly to server response", () => {
    const { result } = renderHook(() => useSubscriptionPlans());

    expect(result.current.filteredPlans).toEqual(mockPlans);
    expect(result.current.totalResults).toBe(25);
  });
});

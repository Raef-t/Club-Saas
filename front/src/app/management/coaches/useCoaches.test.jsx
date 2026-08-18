import { act, renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCoaches } from "./useCoaches";

const { createCoach } = vi.hoisted(() => ({
  createCoach: vi.fn(),
}));

vi.mock("@/lib/api/coachesApi", () => ({
  useGetCoachesQuery: () => ({ data: { data: [] }, isLoading: false, refetch: vi.fn() }),
  useGetCoachQuery: () => ({ data: undefined, isFetching: false }),
  useCreateCoachMutation: () => [createCoach, { isLoading: false }],
  useUpdateCoachMutation: () => [vi.fn(), { isLoading: false }],
  useUpdateCoachPhotoMutation: () => [vi.fn(), { isLoading: false }],
  useDeleteCoachMutation: () => [vi.fn(), { isLoading: false }],
}));

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/activitiesApi", () => ({
  useGetActivitiesQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/api/subscriptionPlansApi", () => ({
  useGetSubscriptionPlansQuery: () => ({ data: { data: [] } }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({
    selectedBranchId: "all",
    setSelectedBranchId: vi.fn(),
  }),
}));

describe("coach creation", () => {
  beforeEach(() => {
    createCoach.mockReset();
    createCoach.mockReturnValue({ unwrap: vi.fn().mockResolvedValue({ status: "success" }) });
  });

  it("creates a coach without requiring the edit-only modification reason", async () => {
    const { result } = renderHook(() => useCoaches());

    await act(async () => {
      await result.current.handleCreate({
        first_name: "أحمد",
        last_name: "محمد",
        gender: "male",
        dob: "1990-01-01",
        phone_number: "0999999999",
        country_code: "+963",
        address: "",
        branch_ids: [5],
        experience_years: 3,
        start_date: "2026-08-18",
        work_status: "active",
        employment_type: "commission_based",
        base_salary: 0,
        default_commission_rate: 50,
        work_types: ["equipment"],
        activity_ids: [8],
        shifts: [],
        photo: null,
      });
    });

    expect(createCoach).toHaveBeenCalledOnce();
    const submittedFormData = createCoach.mock.calls[0][0];
    expect(submittedFormData.get("reason")).toBeNull();
    expect(submittedFormData.get("default_commission_rate")).toBe("50");
  });
});

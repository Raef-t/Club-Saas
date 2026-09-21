import { act, renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { createCoachUpdatePayload, normalizeCoachEmploymentFilter, useCoaches } from "./useCoaches";

const { createCoach, updateCoach, updateCoachPhoto } = vi.hoisted(() => ({
  createCoach: vi.fn(),
  updateCoach: vi.fn(),
  updateCoachPhoto: vi.fn(),
}));

vi.mock("@/lib/api/coachesApi", () => ({
  useGetCoachesQuery: () => ({ data: { data: [] }, isLoading: false, refetch: vi.fn() }),
  useGetCoachQuery: () => ({ data: undefined, isFetching: false }),
  useCreateCoachMutation: () => [createCoach, { isLoading: false }],
  useUpdateCoachMutation: () => [updateCoach, { isLoading: false }],
  useUpdateCoachPhotoMutation: () => [updateCoachPhoto, { isLoading: false }],
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
    updateCoach.mockReset();
    updateCoachPhoto.mockReset();
    createCoach.mockReturnValue({ unwrap: vi.fn().mockResolvedValue({ status: "success" }) });
    updateCoach.mockReturnValue({ unwrap: vi.fn().mockResolvedValue({ status: "success" }) });
    updateCoachPhoto.mockReturnValue({ unwrap: vi.fn().mockResolvedValue({ status: "success" }) });
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
        private_commission_rate: 70,
        work_types: ["equipment"],
        activity_ids: [8],
        shifts: [],
        photo: null,
      });
    });

    expect(createCoach).toHaveBeenCalledOnce();
    const submittedFormData = createCoach.mock.calls[0][0];
    expect(submittedFormData.get("reason")).toBeNull();
    expect(submittedFormData.get("phone_number")).toBe("0999999999");
    expect(submittedFormData.get("country_code")).toBe("+963");
    expect(submittedFormData.getAll("branch_ids[]")).toEqual(["5"]);
    expect(submittedFormData.get("default_commission_rate")).toBe("50");
    expect(submittedFormData.get("private_commission_rate")).toBe("70");
  });

  it("updates both commission rates and sends the modification reason", async () => {
    const { result } = renderHook(() => useCoaches({ selectedCoachId: 12 }));

    await act(async () => {
      await result.current.handleUpdate({
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
        default_commission_rate: 15.5,
        private_commission_rate: 70,
        work_types: ["equipment", "activities"],
        activity_ids: [8, 9],
        shifts: [],
        reason: "تحديث نسب المدرب",
      });
    });

    expect(updateCoach).toHaveBeenCalledOnce();
    const submittedBody = updateCoach.mock.calls[0][0].body;
    expect(submittedBody).not.toBeInstanceOf(FormData);
    expect(submittedBody).toMatchObject({
      phone_number: "0999999999",
      country_code: "+963",
      branch_ids: [5],
      default_commission_rate: 15.5,
      private_commission_rate: 70,
      reason: "تحديث نسب المدرب",
    });
  });

  it("deletes the coach photo when photoChanged is true and photo is null", async () => {
    const { result } = renderHook(() => useCoaches({ selectedCoachId: 12 }));

    await act(async () => {
      await result.current.handleUpdate({
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
        employment_type: "fixed_salary",
        base_salary: 1000,
        default_commission_rate: 0,
        private_commission_rate: 0,
        work_types: [],
        activity_ids: [],
        shifts: [],
        photo: null,
        photoChanged: true,
      });
    });

    expect(updateCoach).toHaveBeenCalledOnce();
    expect(updateCoachPhoto).toHaveBeenCalledOnce();
    const photoCall = updateCoachPhoto.mock.calls[0][0];
    expect(photoCall.id).toBe(12);
    expect(photoCall.body.get("delete_photo")).toBe("1");
    expect(photoCall.body.get("photo")).toBeNull();
  });

  it("updates the coach photo when photoChanged is true and photo is a File", async () => {
    const { result } = renderHook(() => useCoaches({ selectedCoachId: 12 }));
    const mockFile = new File(["dummy content"], "coach.png", { type: "image/png" });

    await act(async () => {
      await result.current.handleUpdate({
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
        employment_type: "fixed_salary",
        base_salary: 1000,
        default_commission_rate: 0,
        private_commission_rate: 0,
        work_types: [],
        activity_ids: [],
        shifts: [],
        photo: mockFile,
        photoChanged: true,
      });
    });

    expect(updateCoach).toHaveBeenCalledOnce();
    expect(updateCoachPhoto).toHaveBeenCalledOnce();
    const photoCall = updateCoachPhoto.mock.calls[0][0];
    expect(photoCall.id).toBe(12);
    expect(photoCall.body.get("photo")).toBe(mockFile);
    expect(photoCall.body.get("delete_photo")).toBeNull();
  });

  it("does not call updateCoachPhoto when photoChanged is false", async () => {
    const { result } = renderHook(() => useCoaches({ selectedCoachId: 12 }));

    await act(async () => {
      await result.current.handleUpdate({
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
        employment_type: "fixed_salary",
        base_salary: 1000,
        default_commission_rate: 0,
        private_commission_rate: 0,
        work_types: [],
        activity_ids: [],
        shifts: [],
        photo: "https://example.com/coach.jpg",
        photoChanged: false,
      });
    });

    expect(updateCoach).toHaveBeenCalledOnce();
    expect(updateCoachPhoto).not.toHaveBeenCalled();
  });
});

describe("coach update payload", () => {
  it("keeps the country code separate and falls back to the selected branch", () => {
    expect(
      createCoachUpdatePayload(
        {
          first_name: " أحمد ",
          last_name: " محمد ",
          gender: "male",
          dob: "1990-01-01",
          phone_number: " 0991234567 ",
          country_code: " +963 ",
          address: "",
          branch_ids: [],
          experience_years: "3",
          start_date: "2026-08-18",
          work_status: "active",
          employment_type: "fixed_salary",
          base_salary: "1000",
          default_commission_rate: "0",
          private_commission_rate: "0",
          reason: " تحديث البيانات ",
          work_types: [],
          activity_ids: [],
          shifts: [],
        },
        "7",
      ),
    ).toMatchObject({
      first_name: "أحمد",
      last_name: "محمد",
      phone_number: "0991234567",
      country_code: "+963",
      branch_ids: [7],
      reason: "تحديث البيانات",
    });
  });
});

describe("coach employment filters", () => {
  it.each([
    ["fixed_salary", "fixed_salary"],
    ["راتب", "fixed_salary"],
    ["commission_based", "commission_based"],
    ["نسبة", "commission_based"],
    ["hybrid", "hybrid"],
    ["نسبة وراتب", "hybrid"],
    ["unknown", "all"],
  ])("normalizes %s to %s", (input, expected) => {
    expect(normalizeCoachEmploymentFilter(input)).toBe(expected);
  });
});

import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { CoachCreateForm } from "./CoachForm";

vi.mock("@/lib/api/branchesApi", () => {
  const settingsQuery = {
    currentData: { data: { private_subscription_commission: "15.5" } },
    isFetching: false,
    error: null,
  };
  const shiftsQuery = { currentData: { data: [] }, isFetching: false };

  return {
    useGetBranchSettingsQuery: () => settingsQuery,
    useGetBranchShiftsQuery: () => shiftsQuery,
  };
});

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "5" }),
}));

vi.mock("@/lib/TimeFormatContext", () => ({
  useTimeFormat: () => ({ formatTime: (value) => value }),
}));

describe("coach private-training commissions", () => {
  it("loads the club share from branch settings and calculates the coach share", async () => {
    render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[{ id: 8, name: "أجهزة خاص" }]}
        initialValues={{
          first_name: "أحمد",
          last_name: "محمد",
          branch_ids: [5],
          activity_ids: [8],
          private_club_commission_rate: "",
          default_commission_rate: "0",
        }}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    const clubInput = await screen.findByLabelText(/نسبة النادي من التدريب الخاص/);
    const coachInput = screen.getByLabelText(/نسبة المدرب/);

    await waitFor(() => {
      expect(clubInput).toHaveValue(15.5);
      expect(coachInput).toHaveValue(84.5);
    });

    fireEvent.change(clubInput, { target: { value: "20" } });
    expect(clubInput).toHaveValue(20);
    expect(coachInput).toHaveValue(80);
    expect(coachInput).toBeDisabled();
  });
});

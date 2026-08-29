import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { CoachCreateForm } from "./CoachForm";

afterEach(cleanup);

vi.mock("@/lib/api/branchesApi", () => {
  const settingsQuery = {
    currentData: {
      data: {
        private_subscription_commission: "15.5",
        default_coach_commission_percentage: "35",
      },
    },
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

  it("shows the highlighted commission card for a percentage-based activity", async () => {
    const { getByRole } = render(
      <CoachCreateForm
        formId="coach-form"
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[
          {
            id: 9,
            name: "زومبا",
            activity_type: { name: { ar: "حصة جماعية" } },
          },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(getByRole("button", { name: /اختر نشاطاً لإضافته/ }));
    fireEvent.click(await screen.findByRole("option", { name: "زومبا" }));

    const clubInput = await screen.findByLabelText(/نسبة النادي من الفعالية/);
    const coachInput = screen.getByLabelText(/نسبة المدرب من الفعالية/);

    expect(clubInput).toHaveValue(65);
    expect(clubInput).toBeDisabled();
    expect(coachInput).toHaveValue(35);

    fireEvent.change(coachInput, { target: { value: "40" } });
    expect(coachInput).toHaveValue(40);
    await waitFor(() => expect(clubInput).toHaveValue(60));
  });
});

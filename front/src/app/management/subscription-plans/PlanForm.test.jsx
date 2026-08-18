import { fireEvent, render, screen, within } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { PlanForm } from "./SubscriptionPlansClient";

const { coach } = vi.hoisted(() => ({
  coach: {
    id: 44,
    person: { full_name: "كابتن دانية" },
    details: { default_commission_rate: "50.00" },
  },
}));

vi.mock("@/lib/api/coachesApi", () => ({
  useGetCoachesQuery: () => ({ data: { data: [coach] }, isLoading: false }),
  useGetCoachQuery: () => ({
    currentData: { data: coach },
    isFetching: false,
  }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "5" }),
}));

describe("private-equipment activity amounts", () => {
  it("shows and updates the coach and club amounts under the activity price", () => {
    render(
      <PlanForm
        mode="create"
        initialValues={{
          branch_id: "5",
          name: "أجهزة خاص - كابتن دانية",
          sessions_per_week: "",
          session_count: "",
          price: "300",
          max_subscribers: "50",
          is_active: true,
          status: "active",
          gender_restriction: "mixed",
          activities: [{ activity_id: "8", coach_id: "44" }],
          session_templates: [],
          is_unlimited_subscribers: false,
          reason: "",
        }}
        branches={[{ id: 5, name: "الفرع الرئيسي" }]}
        activities={[{ id: 8, name: "أجهزة خاص" }]}
        coaches={[coach]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    const coachCard = screen.getByText("المبلغ الذي يحصل عليه المدرب").parentElement;
    const clubCard = screen.getByText("المبلغ الذي يحصل عليه النادي").parentElement;

    expect(within(coachCard).getByText("150 ل.س")).toBeInTheDocument();
    expect(within(coachCard).getByText("50%")).toBeInTheDocument();
    expect(within(clubCard).getByText("150 ل.س")).toBeInTheDocument();
    expect(within(clubCard).getByText("50%")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/السعر/), { target: { value: "400" } });

    expect(within(coachCard).getByText("200 ل.س")).toBeInTheDocument();
    expect(within(clubCard).getByText("200 ل.س")).toBeInTheDocument();
  });
});

import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { PlanForm } from "./SubscriptionPlansClient";

const { coach } = vi.hoisted(() => ({
  coach: {
    id: 44,
    person: { full_name: "كابتن دانية" },
    details: {
      default_commission_rate: "0.00",
      private_commission_rate: "100.00",
    },
  },
}));

vi.mock("@/lib/api/coachesApi", () => ({
  useGetCoachesQuery: () => ({ data: { data: [coach] }, isLoading: false }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "5" }),
}));

afterEach(cleanup);

describe("private-equipment activity amounts", () => {
  it("collects the coach and club prices and derives the base price", () => {
    const onSubmit = vi.fn();
    const { container } = render(
      <PlanForm
        mode="create"
        initialValues={{
          branch_id: "5",
          name: "أجهزة خاص - كابتن دانية",
          sessions_per_week: "",
          session_count: "",
          price: "300",
          coach_price: "200",
          branch_price: "100",
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
        activities={[
          {
            id: 8,
            branch_id: 5,
            name: "أجهزة خاص",
            activity_type: {
              is_private_equipment: true,
              is_session_based: false,
              is_daily_entry: false,
              has_unlimited_subscribers: true,
            },
          },
        ]}
        coaches={[coach]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByText("300 ل.س")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/سعر الكوتش/), { target: { value: "250" } });

    expect(screen.getByText("350 ل.س")).toBeInTheDocument();
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ price: 350, coach_price: 250, branch_price: 100 }),
    );
  });

  it("reloads branch activities before deciding whether the prices are private", () => {
    render(
      <PlanForm
        mode="create"
        initialValues={{
          branch_id: "5",
          name: "أجهزة خاص - الفرع الأول",
          sessions_per_week: "",
          session_count: "",
          price: "300",
          coach_price: "200",
          branch_price: "100",
          max_subscribers: "50",
          is_active: true,
          status: "active",
          gender_restriction: "mixed",
          activities: [{ activity_id: "8", coach_id: "44" }],
          session_templates: [],
          is_unlimited_subscribers: false,
          reason: "",
        }}
        branches={[
          { id: 5, name: "الفرع الأول" },
          { id: 6, name: "الفرع الثاني" },
        ]}
        activities={[
          {
            id: 8,
            branch_id: 5,
            name: "أجهزة خاص - الأول",
            activity_type: {
              is_private_equipment: true,
              is_session_based: false,
              is_daily_entry: false,
              has_unlimited_subscribers: true,
            },
          },
          {
            id: 9,
            branch_id: 6,
            name: "أجهزة خاص - الثاني",
            activity_type: {
              is_private_equipment: true,
              is_session_based: false,
              is_daily_entry: false,
              has_unlimited_subscribers: true,
            },
          },
        ]}
        coaches={[coach]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "الفرع *" }));
    fireEvent.click(screen.getByRole("option", { name: "الفرع الثاني" }));
    fireEvent.click(screen.getByRole("button", { name: "النشاط الرياضي" }));

    expect(screen.queryByRole("option", { name: "أجهزة خاص - الأول" })).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole("option", { name: "أجهزة خاص - الثاني" }));

    expect(screen.getByLabelText(/سعر الكوتش/)).toBeInTheDocument();
    expect(screen.getByLabelText(/سعر النادي/)).toBeInTheDocument();
  });
});

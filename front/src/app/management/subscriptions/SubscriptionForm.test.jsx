import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { SubscriptionCreateForm } from "./SubscriptionForm";

afterEach(cleanup);

describe("subscription create validation", () => {
  it("renders each date validation message only once", () => {
    const { container } = render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.submit(container.querySelector("form"));

    expect(screen.getAllByText("تاريخ بداية الاشتراك مطلوب")).toHaveLength(1);
    expect(screen.getAllByText("تاريخ نهاية الاشتراك مطلوب")).toHaveLength(1);
  });

  it("shows two priced receipt fields for a private plan", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          {
            id: 76,
            name: "اشتراك خاص",
            base_price: "350.00",
            coach_price: "200.00",
            branch_price: "150.00",
          },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toBeInTheDocument();
    expect(screen.queryByLabelText(/^رقم الإيصال/)).not.toBeInTheDocument();
    expect(screen.getByText("(200 ل.س)")).toBeInTheDocument();
    expect(screen.getByText("(150 ل.س)")).toBeInTheDocument();
  });
});

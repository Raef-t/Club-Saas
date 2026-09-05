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
});

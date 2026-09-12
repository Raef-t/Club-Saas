import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import RenewSubscriptionModal from "./RenewSubscriptionModal";

afterEach(cleanup);

const expiredSubscription = {
  id: 41,
  total_amount: 300,
  member: { person: { full_name: "أحمد محمد" } },
  plan: { id: 9, name: "الاشتراك السابق", base_price: 300 },
};

describe("RenewSubscriptionModal", () => {
  it("starts with the previous plan and updates the amount when the plan changes", async () => {
    const handleSubmit = vi.fn(async () => true);
    render(
      <RenewSubscriptionModal
        open
        subscription={expiredSubscription}
        plans={[expiredSubscription.plan, { id: 10, name: "اشتراك اللياقة", base_price: 450 }]}
        onClose={vi.fn()}
        onSubmit={handleSubmit}
      />,
    );

    const planDropdown = screen.getByRole("button", {
      name: "اسم الاشتراك السابق مع إمكانية التعديل",
    });
    const paidAmount = screen.getByLabelText(/الكمية المدفوعة/);

    expect(planDropdown).toHaveTextContent("الاشتراك السابق");
    expect(paidAmount).toHaveValue(300);

    fireEvent.click(planDropdown);
    fireEvent.click(screen.getByRole("option", { name: "اشتراك اللياقة" }));
    expect(paidAmount).toHaveValue(450);

    fireEvent.change(screen.getByLabelText(/رقم الإيصال/), {
      target: { value: " REC-RENEW-01 " },
    });
    fireEvent.change(paidAmount, { target: { value: "425" } });
    fireEvent.click(screen.getByRole("button", { name: "تأكيد التجديد" }));

    await waitFor(() =>
      expect(handleSubmit).toHaveBeenCalledWith({
        plan_id: 10,
        paid_amount: 425,
        payment_method: "cash",
        receipt_number: "REC-RENEW-01",
      }),
    );
  });

  it("shows validation messages before submitting incomplete payment data", async () => {
    const handleSubmit = vi.fn();
    render(
      <RenewSubscriptionModal
        open
        subscription={expiredSubscription}
        plans={[expiredSubscription.plan]}
        onClose={vi.fn()}
        onSubmit={handleSubmit}
      />,
    );

    fireEvent.change(screen.getByLabelText(/الكمية المدفوعة/), { target: { value: "" } });
    fireEvent.click(screen.getByRole("button", { name: "تأكيد التجديد" }));

    expect(await screen.findByText("رقم الإيصال مطلوب")).toBeInTheDocument();
    expect(screen.getByText("المبلغ المدفوع مطلوب")).toBeInTheDocument();
    expect(handleSubmit).not.toHaveBeenCalled();
  });
});

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

  it("supports private equipment plan renewal with split receipts and auto-calculated total", async () => {
    const handleSubmit = vi.fn(async () => true);
    const privatePlan = {
      id: 25,
      name: "أجهزة خاص",
      is_private_equipment: true,
      coach_price: 300,
      branch_price: 200,
      base_price: 500,
    };

    render(
      <RenewSubscriptionModal
        open
        subscription={expiredSubscription}
        plans={[expiredSubscription.plan, privatePlan]}
        onClose={vi.fn()}
        onSubmit={handleSubmit}
      />,
    );

    // Switch to private plan
    const planDropdown = screen.getByRole("button", {
      name: "اسم الاشتراك السابق مع إمكانية التعديل",
    });
    fireEvent.click(planDropdown);
    fireEvent.click(screen.getByRole("option", { name: "أجهزة خاص" }));

    // Should display split receipt and paid amount fields
    const branchPaidInput = screen.getByLabelText(/مدفوع النادي/);
    const coachPaidInput = screen.getByLabelText(/مدفوع الكوتش/);
    const branchReceiptInput = screen.getByLabelText(/رقم إيصال النادي/);
    const coachReceiptInput = screen.getByLabelText(/رقم إيصال الكوتش/);

    expect(branchPaidInput).toHaveValue(200);
    expect(coachPaidInput).toHaveValue(300);

    // Update amounts and receipts
    fireEvent.change(branchPaidInput, { target: { value: "220" } });
    fireEvent.change(coachPaidInput, { target: { value: "330" } });
    fireEvent.change(branchReceiptInput, { target: { value: "REC-BR-99" } });
    fireEvent.change(coachReceiptInput, { target: { value: "REC-CH-99" } });

    // Submit
    fireEvent.click(screen.getByRole("button", { name: "تأكيد التجديد" }));

    await waitFor(() =>
      expect(handleSubmit).toHaveBeenCalledWith({
        plan_id: 25,
        paid_amount: 550,
        payment_method: "cash",
        receipt_number: "REC-BR-99",
        branch_paid_amount: 220,
        coach_paid_amount: 330,
        branch_receipt_number: "REC-BR-99",
        coach_receipt_number: "REC-CH-99",
      }),
    );
  });
});


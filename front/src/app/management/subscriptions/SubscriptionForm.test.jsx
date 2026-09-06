import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { SubscriptionCreateForm, SubscriptionEditForm } from "./SubscriptionForm";

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

describe("subscription edit receipts", () => {
  it("shows and submits the existing receipt numbers for a private plan", () => {
    const onSubmit = vi.fn();
    const plan = {
      id: 76,
      name: "اشتراك خاص",
      coach_price: "200.00",
      branch_price: "150.00",
    };
    const { container } = render(
      <SubscriptionEditForm
        subscription={{
          id: 10,
          member_id: 1,
          plan_id: 76,
          plan,
          months_count: 1,
          start_date: "2026-08-01",
          end_date: "2026-08-31",
          status: "active",
          paid_amount: "350.00",
          coach_receipt_number: "REC-COACH-001",
          revenue_split: { branch_receipt_number: "REC-CLUB-001" },
        }}
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[plan]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toHaveValue("REC-COACH-001");
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toHaveValue("REC-CLUB-001");

    fireEvent.change(screen.getByLabelText(/سبب التعديل/), {
      target: { value: "تصحيح بيانات الإيصالات" },
    });
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        payment_method: "cash",
        coach_receipt_number: "REC-COACH-001",
        branch_receipt_number: "REC-CLUB-001",
      }),
    );
  });

  it("locks the paid amount and keeps its original value in an employee update", () => {
    const onSubmit = vi.fn();
    const { container } = render(
      <SubscriptionEditForm
        subscription={{
          member_id: 1,
          plan_id: 2,
          months_count: 1,
          start_date: "2026-08-01",
          end_date: "2026-08-31",
          status: "active",
          paid_amount: "300.00",
          receipt_number: "REC-001",
        }}
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: "300.00" }]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/المبلغ المدفوع/)).toBeDisabled();

    fireEvent.change(screen.getByLabelText(/سبب التعديل/), {
      target: { value: "تعديل بيانات الاشتراك" },
    });
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalled();
    expect(onSubmit.mock.calls[0][0]).toHaveProperty("paid_amount", 300);
  });

  it("allows an admin to edit and submit the paid amount", () => {
    const onSubmit = vi.fn();
    const { container } = render(
      <SubscriptionEditForm
        subscription={{
          member_id: 1,
          plan_id: 2,
          months_count: 1,
          start_date: "2026-08-01",
          end_date: "2026-08-31",
          status: "active",
          paid_amount: "300.00",
          receipt_number: "REC-001",
        }}
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: "300.00" }]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
        canEditPaidAmount
      />,
    );

    const paidAmount = screen.getByLabelText(/المبلغ المدفوع/);
    expect(paidAmount).toBeEnabled();
    fireEvent.change(paidAmount, { target: { value: "425" } });
    fireEvent.change(screen.getByLabelText(/سبب التعديل/), {
      target: { value: "تصحيح المبلغ المدفوع" },
    });
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ paid_amount: 425 }));
  });
});

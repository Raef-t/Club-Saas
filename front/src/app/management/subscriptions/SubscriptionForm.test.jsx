import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { SubscriptionCreateForm, SubscriptionEditForm } from "./SubscriptionForm";

afterEach(cleanup);

describe("subscription create validation", () => {
  it("filters plans by activity type and clears the selected plan", () => {
    const onActivityTypeChange = vi.fn();
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        activityTypes={[
          { id: 1, name: "تدريب عام" },
          { id: 7, name: "أنشطة لياقة" },
        ]}
        selectedActivityTypeId="1"
        onActivityTypeChange={onActivityTypeChange}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByRole("button", { name: "خطة الاشتراك" })).toHaveTextContent("اشتراك شهري");
    expect(screen.getByRole("button", { name: "نوع النشاط" })).toHaveTextContent("تدريب عام");
    fireEvent.click(screen.getByRole("button", { name: "نوع النشاط" }));
    expect(screen.getByRole("option", { name: "الكل" })).toBeInTheDocument();
    fireEvent.click(screen.getByRole("option", { name: "أنشطة لياقة" }));

    expect(onActivityTypeChange).toHaveBeenCalledWith("7");
    expect(screen.getByRole("button", { name: "خطة الاشتراك" })).toHaveTextContent("اختر الخطة");
  });

  it("allows selecting 'الكل' to clear the activity type filter and resets the selected plan", () => {
    const onActivityTypeChange = vi.fn();
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        activityTypes={[
          { id: 1, name: "تدريب عام" },
          { id: 7, name: "أنشطة لياقة" },
        ]}
        selectedActivityTypeId="7"
        onActivityTypeChange={onActivityTypeChange}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "نوع النشاط" }));
    fireEvent.click(screen.getByRole("option", { name: "الكل" }));

    expect(onActivityTypeChange).toHaveBeenCalledWith("");
    expect(screen.getByRole("button", { name: "خطة الاشتراك" })).toHaveTextContent("اختر الخطة");
  });

  it("shows a dedicated empty state when an activity type has no available plans", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[]}
        activityTypes={[{ id: 7, name: "أنشطة لياقة" }]}
        selectedActivityTypeId="7"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByText("لا توجد باقات اشتراك متاحة لنوع النشاط المحدد")).toBeInTheDocument();
  });

  it("searches for a player by name without displaying membership numbers", () => {
    render(
      <SubscriptionCreateForm
        members={[
          { id: 1, member_number: "501", person: { full_name: "أحمد خالد" } },
          { id: 2, member_number: "762", person: { full_name: "لينا محمود" } },
        ]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "اللاعب العضو" }));

    const searchInput = screen.getByRole("textbox", {
      name: "ابحث عن اللاعب بالاسم...",
    });
    fireEvent.change(searchInput, { target: { value: "لينا" } });
    expect(screen.getByRole("option", { name: /لينا محمود/ })).toBeInTheDocument();
    expect(screen.queryByRole("option", { name: /أحمد خالد/ })).not.toBeInTheDocument();
    expect(screen.queryByText(/501|762|رقم العضوية/)).not.toBeInTheDocument();
  });

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
  it("keeps two receipt fields for an existing split subscription without plan prices", () => {
    const onSubmit = vi.fn();
    const { container } = render(
      <SubscriptionEditForm
        subscription={{
          id: 145,
          member_id: 1,
          plan_id: 83,
          plan: {
            id: 83,
            name: "الاشتراك الذهبي",
            coach_price: null,
            branch_price: null,
          },
          months_count: 1,
          start_date: "2026-10-01",
          end_date: "2026-11-01",
          status: "active",
          paid_amount: "350.00",
          receipt_number: "REC-CLUB-001",
          coach_receipt_number: "REC-COACH-001",
          branch_receipt_number: "REC-CLUB-001",
          payments: [
            { reason: "دفعة اشتراك المدرب", amount: 175 },
            { reason: "دفعة اشتراك النادي", amount: 175 },
          ],
        }}
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toHaveValue("REC-COACH-001");
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toHaveValue("REC-CLUB-001");
    expect(screen.queryByLabelText(/^رقم الإيصال/)).not.toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/رقم إيصال الكوتش/), {
      target: { value: "310" },
    });
    fireEvent.change(screen.getByLabelText(/رقم إيصال النادي/), {
      target: { value: "310" },
    });
    fireEvent.change(screen.getByLabelText(/سبب التعديل/), {
      target: { value: "تصحيح أرقام الإيصالات" },
    });
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        coach_receipt_number: "310",
        branch_receipt_number: "310",
        coach_paid_amount: 175,
        branch_paid_amount: 175,
      }),
    );
  });

  it("searches for a player by name while editing a subscription", () => {
    render(
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
        members={[
          { id: 1, member_number: "MEM-1", person: { full_name: "أحمد خالد" } },
          { id: 2, member_number: "MEM-2", person: { full_name: "لينا محمود" } },
        ]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "اللاعب العضو *" }));
    const searchInput = screen.getByRole("textbox", { name: "ابحث عن اللاعب بالاسم..." });
    fireEvent.change(searchInput, { target: { value: "لينا" } });

    expect(screen.getByRole("option", { name: "لينا محمود" })).toBeInTheDocument();
    expect(screen.queryByRole("option", { name: "أحمد خالد" })).not.toBeInTheDocument();
    expect(screen.queryByText(/MEM-1|MEM-2/)).not.toBeInTheDocument();
  });

  it("filters available plans by activity type while editing and clears the old plan", () => {
    const onActivityTypeChange = vi.fn();
    render(
      <SubscriptionEditForm
        subscription={{
          member_id: 1,
          plan_id: 2,
          plan: { id: 2, name: "اشتراك قديم", base_price: 300 },
          months_count: 1,
          start_date: "2026-08-01",
          end_date: "2026-08-31",
          status: "active",
          paid_amount: "300.00",
          receipt_number: "REC-001",
        }}
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك قديم", base_price: 300 }]}
        activityTypes={[
          { id: 1, name: "تدريب عام" },
          { id: 7, name: "أنشطة جماعية" },
        ]}
        selectedActivityTypeId="1"
        onActivityTypeChange={onActivityTypeChange}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByRole("button", { name: "خطة الاشتراك *" })).toHaveTextContent("اشتراك قديم");
    fireEvent.click(screen.getByRole("button", { name: "نوع النشاط" }));
    fireEvent.click(screen.getByRole("option", { name: "أنشطة جماعية" }));

    expect(onActivityTypeChange).toHaveBeenCalledWith("7");
    expect(screen.getByRole("button", { name: "خطة الاشتراك *" })).toHaveTextContent("اختر الخطة");
  });

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
        coach_paid_amount: 200,
        branch_paid_amount: 150,
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

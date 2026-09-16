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

  it("keeps the private-plan fields usable while its plans are still unavailable", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[]}
        activityTypes={[
          { id: 2, code: "private_training", name: "تدريب خاص", is_private_equipment: true },
        ]}
        selectedActivityTypeId="2"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByText("لا توجد باقات اشتراك متاحة لنوع النشاط المحدد")).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toBeInTheDocument();
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
        activityTypes={[
          { id: 2, code: "private_training", name: "تدريب خاص", is_private_equipment: true },
        ]}
        selectedActivityTypeId="2"
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

  it("shows only the general receipt for a non-private type even when its plan has split prices", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          {
            id: 78,
            name: "حصة جماعية",
            base_price: "350.00",
            coach_price: "200.00",
            branch_price: "150.00",
          },
        ]}
        activityTypes={[
          { id: 3, code: "group_class", name: "حصة جماعية", is_private_equipment: false },
        ]}
        selectedActivityTypeId="3"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/^رقم الإيصال/)).toBeInTheDocument();
    expect(screen.queryByLabelText(/رقم إيصال الكوتش/)).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/رقم إيصال النادي/)).not.toBeInTheDocument();
  });

  it("shows private receipt fields when the API marks a base-price-only equipment plan", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          {
            id: 77,
            name: "اشتراك أجهزة خاصة",
            base_price: "350.00",
            coach_price: null,
            branch_price: null,
            is_private_equipment: true,
          },
        ]}
        activityTypes={[
          { id: 2, code: "private_training", name: "أجهزة خاص", is_private_equipment: true },
        ]}
        selectedActivityTypeId="2"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toBeInTheDocument();
    expect(screen.queryByLabelText(/^رقم الإيصال/)).not.toBeInTheDocument();
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
            activity_types: [
              {
                id: 2,
                code: "private_training",
                name: "تدريب خاص",
                is_private_equipment: true,
              },
            ],
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
        activityTypes={[
          { id: 2, code: "private_training", name: "تدريب خاص", is_private_equipment: true },
        ]}
        selectedActivityTypeId="2"
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
      activity_types: [
        {
          id: 2,
          code: "private_training",
          name: "تدريب خاص",
          is_private_equipment: true,
        },
      ],
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
        activityTypes={[
          { id: 2, code: "private_training", name: "تدريب خاص", is_private_equipment: true },
        ]}
        selectedActivityTypeId="2"
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

  it("submits the new plan price, split amounts, and recalculated end date for an employee", () => {
    const onSubmit = vi.fn();
    const oldPlan = {
      id: 76,
      name: "اشتراك خاص قديم",
      base_price: "300.00",
      coach_price: "175.00",
      branch_price: "125.00",
    };
    const newPlan = {
      id: 77,
      name: "اشتراك خاص جديد",
      base_price: "650.00",
      coach_price: "400.00",
      branch_price: "250.00",
      is_private_equipment: true,
    };
    const { container } = render(
      <SubscriptionEditForm
        subscription={{
          member_id: 1,
          plan_id: oldPlan.id,
          plan: oldPlan,
          months_count: 2,
          start_date: "2026-08-10",
          end_date: "2026-09-09",
          status: "active",
          paid_amount: oldPlan.base_price,
          coach_receipt_number: "OLD-COACH",
          branch_receipt_number: "OLD-BRANCH",
          payments: [
            { reason: "دفعة اشتراك المدرب", amount: 175 },
            { reason: "دفعة اشتراك النادي", amount: 125 },
          ],
        }}
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[oldPlan, newPlan]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "خطة الاشتراك *" }));
    fireEvent.click(screen.getByRole("option", { name: newPlan.name }));

    expect(screen.getByLabelText(/المبلغ المدفوع/)).toHaveValue(650);
    expect(screen.getByDisplayValue("09/10/2026")).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toHaveValue("");
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toHaveValue("");

    fireEvent.change(screen.getByLabelText(/رقم إيصال الكوتش/), {
      target: { value: "NEW-COACH" },
    });
    fireEvent.change(screen.getByLabelText(/رقم إيصال النادي/), {
      target: { value: "NEW-BRANCH" },
    });
    fireEvent.change(screen.getByLabelText(/سبب التعديل/), {
      target: { value: "تغيير خطة الاشتراك" },
    });
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        plan_id: newPlan.id,
        paid_amount: 650,
        end_date: "2026-10-09",
        coach_paid_amount: 400,
        branch_paid_amount: 250,
      }),
    );
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

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
    expect(screen.queryByRole("option", { name: "الكل" })).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole("option", { name: "أنشطة لياقة" }));

    expect(onActivityTypeChange).toHaveBeenCalledWith("7");
    expect(screen.getByRole("button", { name: "خطة الاشتراك" })).toHaveTextContent("اختر الخطة");
  });

  it("does not offer an 'all' activity type on the create form", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        activityTypes={[
          { id: 1, name: "تدريب عام" },
          { id: 7, name: "أنشطة لياقة" },
        ]}
        selectedActivityTypeId="7"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "نوع النشاط" }));
    expect(screen.queryByRole("option", { name: "الكل" })).not.toBeInTheDocument();
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

  it("searches subscription plans by name", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          { id: 2, name: "اشتراك شهري", base_price: 300 },
          { id: 3, name: "اشتراك سباحة", base_price: 450 },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "خطة الاشتراك" }));
    const searchInput = screen.getByRole("textbox", { name: "ابحث عن خطة الاشتراك..." });
    fireEvent.change(searchInput, { target: { value: "سباحة" } });

    expect(screen.getByRole("option", { name: "اشتراك سباحة" })).toBeInTheDocument();
    expect(screen.queryByRole("option", { name: "اشتراك شهري" })).not.toBeInTheDocument();
  });

  it("defaults the start date to today and keeps it editable", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    const today = new Date();
    const todayDisplay = [
      String(today.getDate()).padStart(2, "0"),
      String(today.getMonth() + 1).padStart(2, "0"),
      today.getFullYear(),
    ].join("/");
    const [startDateInput] = screen.getAllByPlaceholderText("dd/mm/yyyy");

    expect(startDateInput).toHaveValue(todayDisplay);
    expect(startDateInput).toBeEnabled();

    fireEvent.focus(startDateInput);
    fireEvent.change(startDateInput, { target: { value: "20/10/2026" } });
    fireEvent.blur(startDateInput);

    expect(startDateInput).toHaveValue("20/10/2026");
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

  it("renders safely when private activity type is selected but no plan is selected yet", () => {
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

    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toBeInTheDocument();
    expect(screen.queryByLabelText(/^رقم الإيصال/)).not.toBeInTheDocument();
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

    expect(screen.queryByLabelText(/^رقم الإيصال/)).not.toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال الكوتش/)).toBeInTheDocument();
    expect(screen.getByLabelText(/رقم إيصال النادي/)).toBeInTheDocument();
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

describe("subscription discount calculations", () => {
  it("uses the base-price title until a discount is enabled", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300000 }]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByText("السعر الأساسي")).toBeInTheDocument();
    expect(screen.queryByText("السعر الأصلي قبل الحسم")).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("checkbox", { name: "تطبيق حسم" }));

    expect(screen.getByText("السعر الأصلي قبل الحسم")).toBeInTheDocument();
    expect(screen.queryByText("السعر الأساسي")).not.toBeInTheDocument();
  });

  it("calculates the percentage, final price, and proposed payment in both directions", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "دخول يومي", base_price: 300000, is_daily_entry: true }]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("checkbox", { name: "تطبيق حسم" }));
    fireEvent.change(screen.getByLabelText(/السعر النهائي بعد الحسم/), {
      target: { value: "150000" },
    });

    expect(screen.getByLabelText(/^نسبة الحسم/)).toHaveValue(50);
    expect(screen.getByLabelText(/المبلغ المدفوع للاشتراك/)).toHaveValue(150000);

    fireEvent.change(screen.getByLabelText(/^نسبة الحسم/), {
      target: { value: "25" },
    });

    expect(screen.getByLabelText(/السعر النهائي بعد الحسم/)).toHaveValue(225000);
    expect(screen.getByLabelText(/المبلغ المدفوع للاشتراك/)).toHaveValue(225000);
  });

  it("submits the normalized general discount payload", () => {
    const onSubmit = vi.fn();
    const { container } = render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "دخول يومي", base_price: 300000, is_daily_entry: true }]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("checkbox", { name: "تطبيق حسم" }));
    fireEvent.change(screen.getByLabelText(/السعر النهائي بعد الحسم/), {
      target: { value: "150000" },
    });
    fireEvent.change(screen.getByLabelText(/^رقم الإيصال/), {
      target: { value: "REC-DISCOUNT-1" },
    });
    fireEvent.change(screen.getByLabelText(/سبب الحسم/), {
      target: { value: "حسم خاص" },
    });

    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        is_discount: true,
        discount_percentage: 50,
        discount_amount: 150000,
        discount_reason: "حسم خاص",
        paid_amount: 150000,
        currency: "SYP",
      }),
      "normal",
    );
  });

  it("keeps the private total in the form while showing only non-duplicated split controls", () => {
    const onSubmit = vi.fn();
    const { container } = render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          {
            id: 9,
            name: "تدريب خاص",
            base_price: 300000,
            coach_price: 200000,
            branch_price: 100000,
          },
        ]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.queryByLabelText(/المبلغ المدفوع للاشتراك/)).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole("checkbox", { name: "تطبيق حسم" }));
    fireEvent.click(screen.getByRole("button", { name: "حسم منفصل" }));
    expect(screen.queryByLabelText(/حصة الكوتش بعد الحسم/)).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/حصة النادي بعد الحسم/)).not.toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/نسبة حسم الكوتش/), {
      target: { value: "50" },
    });

    expect(screen.getByLabelText(/نسبة حسم الكوتش/)).toHaveValue(50);
    expect(screen.getByLabelText(/نسبة حسم النادي/)).toHaveValue(0);
    expect(screen.getByLabelText(/دفعة الكوتش/)).toHaveValue(100000);
    expect(screen.getByLabelText(/دفعة النادي/)).toHaveValue(100000);

    fireEvent.change(screen.getByLabelText(/رقم إيصال الكوتش/), {
      target: { value: "COACH-1" },
    });
    fireEvent.change(screen.getByLabelText(/رقم إيصال النادي/), {
      target: { value: "BRANCH-1" },
    });
    fireEvent.change(screen.getByLabelText(/سبب الحسم/), {
      target: { value: "حسم للكوتش" },
    });
    fireEvent.submit(container.querySelector("form"));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        paid_amount: 200000,
        coach_paid_amount: 100000,
        branch_paid_amount: 100000,
      }),
      "normal",
    );
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

  it("searches subscription plans by name while editing", () => {
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
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          { id: 2, name: "اشتراك شهري", base_price: 300 },
          { id: 3, name: "اشتراك سباحة", base_price: 450 },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "خطة الاشتراك *" }));
    const searchInput = screen.getByRole("textbox", { name: "ابحث عن خطة الاشتراك..." });
    fireEvent.change(searchInput, { target: { value: "سباحة" } });

    expect(screen.getByRole("option", { name: "اشتراك سباحة" })).toBeInTheDocument();
    expect(screen.queryByRole("option", { name: "اشتراك شهري" })).not.toBeInTheDocument();
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
    expect(screen.queryByRole("option", { name: "الكل" })).not.toBeInTheDocument();
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

    expect(screen.getByLabelText(/^المبلغ المدفوع/)).toHaveValue(1300);
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
        paid_amount: 1300,
        end_date: "2026-10-09",
        coach_paid_amount: 800,
        branch_paid_amount: 500,
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

  it("does not display offers dropdown when no offers match or exist", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        offers={[]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.queryByText(/باقة العروض الترويجية/)).not.toBeInTheDocument();
  });

  it("displays offers dropdown when available offer matches activity type and sets price from offer", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[{ id: 2, name: "اشتراك شهري", base_price: 300 }]}
        activityTypes={[{ id: 5, name: "سباحة" }]}
        selectedActivityTypeId="5"
        offers={[
          {
            id: 10,
            name: "عرض باقة الصيف",
            price: 750,
            is_available: true,
            plans: [
              {
                id: 2,
                name: "اشتراك شهري",
                activity_types: [{ id: 5, name: "سباحة" }],
              },
            ],
          },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    expect(screen.getByText(/باقة العروض الترويجية/)).toBeInTheDocument();

    // Select the offer
    const offerDropdown = screen.getByRole("button", { name: /باقة العروض الترويجية/ });
    fireEvent.click(offerDropdown);
    fireEvent.click(screen.getByRole("option", { name: /عرض باقة الصيف/ }));

    // Paid amount should now be set from the offer
    const paidAmount = screen.getByLabelText(/المبلغ المدفوع/);
    expect(paidAmount).toHaveValue(750);
    expect(screen.getByText(/تم اختيار باقة عرض ترويجي/)).toBeInTheDocument();
  });

  it("displays each activity in single_choice offer directly as an offer option and sets discounted price immediately", () => {
    render(
      <SubscriptionCreateForm
        members={[{ id: 1, person: { full_name: "لاعب تجريبي" } }]}
        plans={[
          { id: 20, name: "أيروبيك - كوتش سارة", base_price: 250 },
          { id: 21, name: "أيروبيك - كوتش ريم", base_price: 250 },
        ]}
        activityTypes={[{ id: 6, name: "أيروبيك" }]}
        selectedActivityTypeId="6"
        offers={[
          {
            id: 15,
            name: "عرض الأيروبيك المميز",
            offer_type: "single_choice",
            price: 200,
            is_available: true,
            plans: [
              {
                id: 20,
                name: "أيروبيك - كوتش سارة",
                base_price: 250,
                current_subscribers: 5,
                max_subscribers: 15,
                activity_types: [{ id: 6, name: "أيروبيك" }],
              },
              {
                id: 21,
                name: "أيروبيك - كوتش ريم",
                base_price: 250,
                current_subscribers: 20,
                max_subscribers: 20,
                activity_types: [{ id: 6, name: "أيروبيك" }],
              },
            ],
          },
        ]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
      />,
    );

    const offerDropdown = screen.getByRole("button", { name: /باقة العروض الترويجية/ });
    fireEvent.click(offerDropdown);

    // Each activity appears directly as an offer option
    const sarahOption = screen.getByRole("option", { name: /أيروبيك - كوتش سارة/ });
    const reemOption = screen.getByRole("option", { name: /أيروبيك - كوتش ريم.*مكتملة السعة/ });

    expect(sarahOption).toBeInTheDocument();
    expect(reemOption).toBeDisabled();

    // Select the available activity option directly
    fireEvent.click(sarahOption);

    // Paid amount should be 200
    const paidAmount = screen.getByLabelText(/المبلغ المدفوع/);
    expect(paidAmount).toHaveValue(200);

    // Should indicate single_choice and show the confirmed plan directly
    expect(screen.getByText(/يختار المشترك فعالية واحدة/)).toBeInTheDocument();
    expect(screen.getByText(/الفعالية المحددة بالعرض:/)).toBeInTheDocument();
    expect(screen.getAllByText("أيروبيك - كوتش سارة").length).toBeGreaterThanOrEqual(1);
    // No redundant secondary plan dropdown
    expect(screen.queryByText(/الفعالية المحددة للاشتراك بسعر العرض/)).not.toBeInTheDocument();
  });
});

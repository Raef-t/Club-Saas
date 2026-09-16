import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import OfferForm from "./OfferForm";

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchesQuery: () => ({
    data: [{ id: 1, name: "الفرع الرئيسي" }],
    isLoading: false,
  }),
}));

vi.mock("@/lib/api/subscriptionPlansApi", () => ({
  useGetSubscriptionPlansQuery: () => ({
    data: [
      {
        id: 10,
        name: "اشتراك سباحة شهري",
        base_price: "1000",
        max_subscribers: 15,
        current_subscribers: 5,
        available_slots: 10,
        activity_type: { id: 1, name: "سباحة" },
        activity_types: [{ id: 1, name: "سباحة" }],
      },
      {
        id: 20,
        name: "اشتراك حديد ولياقة",
        base_price: "800",
        max_subscribers: 10,
        current_subscribers: 6,
        available_slots: 4,
        activity_type: { id: 2, name: "حديد ولياقة" },
        activity_types: [{ id: 2, name: "حديد ولياقة" }],
      },
      {
        id: 30,
        name: "أجهزة عام يومي",
        base_price: "500",
        max_subscribers: 0,
        current_subscribers: 40,
        is_unlimited_subscribers: true,
        available_slots: null,
        activity_type: { id: 2, name: "حديد ولياقة" },
        activity_types: [{ id: 2, name: "حديد ولياقة" }],
      },
    ],
    isLoading: false,
  }),
}));

vi.mock("@/lib/api/activitiesApi", () => ({
  useGetActivityTypesQuery: () => ({
    data: [
      { id: 1, name: "سباحة" },
      { id: 2, name: "حديد ولياقة" },
      { id: 3, name: "حصه جماعيه" },
      { id: 4, name: "حصة جماعية" },
    ],
    isLoading: false,
  }),
  useGetActivitiesQuery: () => ({
    data: [],
    isLoading: false,
  }),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "1" }),
}));

afterEach(cleanup);

describe("OfferForm", () => {
  it("renders the offer form with deduplicated activity types and plans", () => {
    render(
      <OfferForm
        mode="create"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
      />
    );

    expect(screen.getByText("نوع العرض *")).toBeInTheDocument();
    expect(screen.getByText("نوع النشاط *")).toBeInTheDocument();
    expect(screen.getByText("الفعاليات المشمولة في العرض *")).toBeInTheDocument();
    expect(screen.getByText("اشتراك سباحة شهري")).toBeInTheDocument();
    expect(screen.getByText("اشتراك حديد ولياقة")).toBeInTheDocument();
    expect(screen.getByText(/عدد المشتركين المتاح تسجيلهم لهذا العرض:/)).toBeInTheDocument();

    // Verify deduplication: open activity type dropdown
    const buttons = screen.getAllByRole("button");
    const activityTypeBtn = buttons.find((btn) => btn.textContent.includes("جميع أنواع الأنشطة"));
    fireEvent.click(activityTypeBtn);

    // "حصه جماعيه" should only appear ONCE in the options
    const groupOptions = screen.getAllByRole("option").filter((opt) =>
      opt.textContent.includes("حصه جماعيه") || opt.textContent.includes("حصة جماعية")
    );
    expect(groupOptions).toHaveLength(1);
  });

  it("filters plans when an activity type is selected", () => {
    render(
      <OfferForm
        mode="create"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
      />
    );

    // Open activity type dropdown button
    const buttons = screen.getAllByRole("button");
    const activityTypeBtn = buttons.find((btn) => btn.textContent.includes("جميع أنواع الأنشطة"));
    fireEvent.click(activityTypeBtn);

    // Select "سباحة"
    const swimOption = screen.getByRole("option", { name: "سباحة" });
    fireEvent.click(swimOption);

    // Swimming plan should be visible, fitness plan should be filtered out
    expect(screen.getByText("اشتراك سباحة شهري")).toBeInTheDocument();
    expect(screen.queryByText("اشتراك حديد ولياقة")).not.toBeInTheDocument();
  });

  it("calculates available subscriber capacity: limited count when unlimited+limited, and min when both limited", () => {
    render(
      <OfferForm
        mode="create"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
      />
    );

    // 1. Select unlimited plan alone
    fireEvent.click(screen.getByText("أجهزة عام يومي"));
    expect(screen.getByText("غير محدود")).toBeInTheDocument();

    // 2. Select limited plan (10 slots) with the unlimited plan -> capacity is the limited one (10)
    fireEvent.click(screen.getByText("اشتراك سباحة شهري"));
    expect(screen.getByText("10 مقعد متاح")).toBeInTheDocument();

    // 3. Select second limited plan (4 slots) -> capacity is min(10, 4) = 4
    fireEvent.click(screen.getByText("اشتراك حديد ولياقة"));
    expect(screen.getByText("4 مقعد متاح")).toBeInTheDocument();
  });

  it("automatically generates offer name from selected plans and calculates discount", () => {
    render(
      <OfferForm
        mode="create"
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
      />
    );

    // Select swim plan (1000)
    fireEvent.click(screen.getByText("اشتراك سباحة شهري"));

    // Name input should automatically reflect the selected plan name!
    const nameInput = screen.getByDisplayValue("اشتراك سباحة شهري");
    expect(nameInput).toBeInTheDocument();

    // Enter offer price 700
    const priceInput = screen.getByPlaceholderText("أدخل سعر العرض");
    fireEvent.change(priceInput, { target: { value: "700" } });

    // Should show discount
    expect(screen.getByText(/مجموع التوفير في الباقة:/)).toBeInTheDocument();
    expect(screen.getByText(/300 ل\.س \(خصم 30%\)/)).toBeInTheDocument();
  });

  it("allows selecting single_choice offer type from dropdown and submitting", () => {
    const handleSubmit = vi.fn();
    render(
      <OfferForm
        mode="create"
        onSubmit={handleSubmit}
        onCancel={vi.fn()}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
      />
    );

    // Open offer type dropdown
    const buttons = screen.getAllByRole("button");
    const offerTypeBtn = buttons.find((btn) => btn.textContent.includes("باقة"));
    fireEvent.click(offerTypeBtn);

    // Select "يختار المشترك فعالية واحدة"
    const singleChoiceOption = screen.getByRole("option", { name: "يختار المشترك فعالية واحدة" });
    fireEvent.click(singleChoiceOption);

    // Select swim plan (10 slots) + fitness plan (4 slots) -> does NOT take min, shows per-activity seats!
    fireEvent.click(screen.getByText("اشتراك سباحة شهري"));
    fireEvent.click(screen.getByText("اشتراك حديد ولياقة"));
    expect(screen.getByText(/المقاعد المتاحة لكل فعالية:/)).toBeInTheDocument();
    expect(screen.getByText("10 مقعد")).toBeInTheDocument();
    expect(screen.getByText("4 مقعد")).toBeInTheDocument();
    expect(screen.getByText(/14 مقعد متاح إجمالاً/)).toBeInTheDocument();

    // Enter price 200
    const priceInput = screen.getByPlaceholderText("أدخل سعر العرض");
    fireEvent.change(priceInput, { target: { value: "200" } });

    // Submit form
    fireEvent.click(screen.getByText("إنشاء العرض"));

    expect(handleSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        name: "عرض اشتراك سباحة شهري / اشتراك حديد ولياقة",
        offer_type: "single_choice",
        price: 200,
        plans: [10, 20],
      })
    );
  });

  it("toggles start_date and end_date fields when 'فعالية غير محدودة' checkbox is toggled", () => {
    const handleSubmit = vi.fn();
    render(
      <OfferForm
        mode="create"
        onSubmit={handleSubmit}
        onCancel={vi.fn()}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
      />
    );

    // By default, "فعالية غير محدودة" is checked and dates are hidden
    const unlimitedCheckbox = screen.getByRole("checkbox", { name: "فعالية غير محدودة" });
    expect(unlimitedCheckbox).toBeChecked();
    expect(screen.queryByText("من تاريخ")).not.toBeInTheDocument();
    expect(screen.queryByText("إلى تاريخ")).not.toBeInTheDocument();

    // Uncheck "فعالية غير محدودة" -> date fields appear
    fireEvent.click(unlimitedCheckbox);
    expect(unlimitedCheckbox).not.toBeChecked();

    expect(screen.getByText("من تاريخ")).toBeInTheDocument();
    expect(screen.getByText("إلى تاريخ")).toBeInTheDocument();

    // Check again -> date fields hidden again
    fireEvent.click(unlimitedCheckbox);
    expect(unlimitedCheckbox).toBeChecked();
    expect(screen.queryByText("من تاريخ")).not.toBeInTheDocument();
    expect(screen.queryByText("إلى تاريخ")).not.toBeInTheDocument();
  });
});

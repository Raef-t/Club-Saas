import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import OffersClient from "./OffersClient";

const mockOffers = [
  {
    id: 1,
    name: "عرض أيروبيك خاص",
    description: "تختار اللاعبة مدربة أيروبيك واحدة",
    offer_type: "single_choice",
    price: 300,
    is_active: true,
    is_available: true,
    available_slots: 14,
    plans: [
      {
        id: 101,
        name: "أيروبيك كوتش سارة",
        base_price: 350,
        max_subscribers: 15,
        current_subscribers: 5,
        available_slots: 10,
      },
      {
        id: 102,
        name: "أيروبيك كوتش ريم",
        base_price: 400,
        max_subscribers: 10,
        current_subscribers: 6,
        available_slots: 4,
      },
      {
        id: 103,
        name: "أيروبيك كوتش منى",
        base_price: 350,
        max_subscribers: 0,
        current_subscribers: 20,
        is_unlimited_subscribers: true,
        available_slots: null,
      },
    ],
  },
  {
    id: 2,
    name: "باقة اللياقة والسباحة",
    description: "تشمل كل الفعاليات",
    offer_type: "bundle",
    price: 1200,
    is_active: true,
    is_available: true,
    available_slots: 5,
    plans: [
      {
        id: 201,
        name: "اشتراك سباحة شهري",
        base_price: 1000,
      },
      {
        id: 202,
        name: "اشتراك لياقة بدنية",
        base_price: 500,
      },
    ],
  },
];

vi.mock("@/lib/api/offersApi", () => ({
  useGetOffersQuery: () => ({
    data: mockOffers,
    isLoading: false,
    isFetching: false,
    isError: false,
    refetch: vi.fn(),
  }),
  useDeleteOfferMutation: () => [vi.fn(), { isLoading: false }],
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "1" }),
}));

vi.mock("@/lib/PermissionContext", () => ({
  usePermissions: () => ({ can: () => true, isSuperAdmin: true }),
}));

vi.mock("@/components/ui/Toast", () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn() }),
}));

afterEach(cleanup);

describe("OffersClient", () => {
  it("renders single_choice offer with each activity on its own line showing seats and savings", () => {
    render(<OffersClient />);

    // Check single choice offer row
    expect(screen.getAllByText("عرض أيروبيك خاص").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("يختار المشترك فعالية واحدة").length).toBeGreaterThanOrEqual(1);

    // In single_choice: each activity is listed on its row spanning all columns
    expect(screen.getAllByText("أيروبيك كوتش سارة").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("أيروبيك كوتش ريم").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("أيروبيك كوتش منى").length).toBeGreaterThanOrEqual(1);

    // Price column renders the offer price 300 for each activity sub-row
    expect(screen.getAllByText("300 ل.س").length).toBeGreaterThanOrEqual(3);

    // Discount column renders the savings for each activity
    expect(screen.getAllByText("50 ل.س").length).toBeGreaterThanOrEqual(2);
    expect(screen.getAllByText("100 ل.س").length).toBeGreaterThanOrEqual(1);

    // Status column renders capacity badge for each activity
    expect(screen.getAllByText("10 مقعد").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("4 مقعد").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("غير محدود").length).toBeGreaterThanOrEqual(1);

    // Check that 'لكل فعالية' and '(14 مقعد)' and 'مفتوح دائماً' are NOT displayed
    expect(screen.queryByText("لكل فعالية")).not.toBeInTheDocument();
    expect(screen.queryByText(/14 مقعد/)).not.toBeInTheDocument();
    expect(screen.queryByText("مفتوح دائماً")).not.toBeInTheDocument();

    // Edit and Delete actions are available for each sub-row
    expect(screen.getAllByTitle("تعديل").length).toBeGreaterThanOrEqual(3);
    expect(screen.getAllByTitle("حذف").length).toBeGreaterThanOrEqual(3);
  });

  it("renders bundle offer with badges and total package savings", () => {
    render(<OffersClient />);

    expect(screen.getAllByText("باقة اللياقة والسباحة").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("باقة مجمعة").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("اشتراك سباحة شهري").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("اشتراك لياقة بدنية").length).toBeGreaterThanOrEqual(1);

    // Bundle total savings (1500 - 1200 = 300)
    expect(screen.getAllByText("300 ل.س").length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText("للباقة").length).toBeGreaterThanOrEqual(1);
  });
});

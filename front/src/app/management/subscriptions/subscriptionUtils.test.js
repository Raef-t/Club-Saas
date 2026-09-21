import { describe, expect, it } from "vitest";
import {
  calculateDiscountFromFinalPrice,
  calculateDiscountFromPercentage,
  formatSubscriptionMoney,
  getAvailableSubscriptionPlanParams,
  getDefaultSubscriptionActivityTypeId,
  getCurrentMemberSubscription,
  getLocalDateValue,
  getSubscriptionEndDate,
  getSubscriptionDetail,
  getSubscriptionCreatorName,
  getSubscriptionDiscountSummary,
  getSubscriptionActivityTypeId,
  getSubscriptionReceiptNumber,
  getSubscriptionReceiptNumbers,
  getSubscriptionRevenueMonthLabel,
  getSubscriptionOriginalAmounts,
  getSubscriptionSplitPaymentAmounts,
  getSubscriptionRows,
  getSubscriptionStats,
  isDailyEntrySubscriptionPlan,
  isPrivateSubscriptionPlan,
  parseSubscriptionAmount,
  sortSubscriptionsNewestFirst,
} from "./subscriptionUtils";

describe("subscription utilities", () => {
  it("normalizes invalid amounts", () => {
    expect(parseSubscriptionAmount("12.5")).toBe(12.5);
    expect(parseSubscriptionAmount("invalid")).toBe(0);
  });

  it("formats subscription money consistently", () => {
    expect(formatSubscriptionMoney("1200.5")).toBe("1,200.5 ل.س");
  });

  it("supports nested and direct collection responses", () => {
    const rows = [{ id: 1 }];

    expect(getSubscriptionRows({ data: { data: rows } })).toEqual(rows);
    expect(getSubscriptionRows({ data: rows })).toEqual(rows);
    expect(getSubscriptionRows(null)).toEqual([]);
  });

  it("orders subscriptions from newest to oldest without mutating the response rows", () => {
    const rows = [
      { id: 1, created_at: "2026-09-01T10:00:00Z" },
      { id: 3, created_at: "2026-09-03T10:00:00Z" },
      { id: 2, created_at: "2026-09-02T10:00:00Z" },
    ];

    expect(sortSubscriptionsNewestFirst(rows).map((subscription) => subscription.id)).toEqual([
      3, 2, 1,
    ]);
    expect(rows.map((subscription) => subscription.id)).toEqual([1, 3, 2]);
  });

  it("builds available plan filters and omits the activity type when all is selected", () => {
    expect(getAvailableSubscriptionPlanParams("1", "3")).toEqual({
      branch_id: "1",
      available: true,
      activity_type_id: "3",
      per_page: 15,
      page: 1,
    });
    expect(getAvailableSubscriptionPlanParams("all", "")).toEqual({
      available: true,
      per_page: 15,
      page: 1,
    });
  });

  it("uses general training as the default subscription activity type", () => {
    const activityTypes = [
      { id: 2, code: "private_training", name: "تدريب خاص" },
      { id: 1, code: "general_training", name: "تدريب عام" },
      { id: 3, code: "group_class", name: "حصة جماعية" },
    ];

    expect(getDefaultSubscriptionActivityTypeId(activityTypes)).toBe("1");
    expect(getDefaultSubscriptionActivityTypeId([{ id: 9, name: "نوع أول" }])).toBe("9");
    expect(getDefaultSubscriptionActivityTypeId([])).toBe("");
  });

  it("includes the numeric month in the monthly revenue label", () => {
    expect(getSubscriptionRevenueMonthLabel(new Date(2026, 8, 1))).toBe("إجمالي إيرادات الشهر ٩");
  });

  it("reads aggregate statistics from the player subscriptions response", () => {
    expect(
      getSubscriptionStats({
        data: [{ id: 1, status: "active", paid_amount: "50" }],
        stats: {
          active_subscriptions: 45,
          total_subscriptions: 120,
          total_paid_amount: 15400,
          today_revenue: 1200,
        },
      }),
    ).toEqual({
      activeSubscriptions: 45,
      totalSubscriptions: 120,
      totalPaidAmount: 15400,
      todayRevenue: 1200,
    });
  });

  it("keeps legacy subscription responses usable when aggregate statistics are absent", () => {
    expect(
      getSubscriptionStats({
        data: [
          { id: 1, status: "active", paid_amount: "50" },
          { id: 2, status: "finished", paid_amount: "75" },
        ],
        meta: { total: 8 },
      }),
    ).toEqual({
      activeSubscriptions: 1,
      totalSubscriptions: 8,
      totalPaidAmount: 125,
      todayRevenue: 0,
    });
  });

  it("extracts a subscription detail safely", () => {
    expect(getSubscriptionDetail({ data: { id: 1 } })).toEqual({ id: 1 });
    expect(getSubscriptionDetail(null)).toBeNull();
  });

  it("reads the employee who created the subscription", () => {
    expect(getSubscriptionCreatorName({ created_by: { id: 143, name: "ISS Group" } })).toBe(
      "ISS Group",
    );
    expect(getSubscriptionCreatorName({ created_by: { full_name: "موظف الاستقبال" } })).toBe(
      "موظف الاستقبال",
    );
    expect(getSubscriptionCreatorName({})).toBeNull();
  });

  it("reads the receipt number from the subscription or related payments", () => {
    expect(getSubscriptionReceiptNumber({ receipt_number: "REC-10" })).toBe("REC-10");
    expect(
      getSubscriptionReceiptNumber({
        receipt_number: null,
        payments: [{ receipt_number: "PAY-11" }],
      }),
    ).toBe("PAY-11");
    expect(getSubscriptionReceiptNumber({ invoices: [{ receipt_number: "INV-12" }] })).toBe(
      "INV-12",
    );
    expect(getSubscriptionReceiptNumber(null)).toBeNull();
  });

  it("reads private-plan receipts from the subscription and revenue split", () => {
    expect(
      getSubscriptionReceiptNumbers({
        coach_receipt_number: "REC-COACH-001",
        revenue_split: { branch_receipt_number: "REC-CLUB-001" },
      }),
    ).toEqual({
      receiptNumber: null,
      coachReceiptNumber: "REC-COACH-001",
      branchReceiptNumber: "REC-CLUB-001",
    });
  });

  it("detects private plans from their flag, type, or complete split prices", () => {
    expect(isPrivateSubscriptionPlan({ coach_price: "200.00", branch_price: "150.00" })).toBe(true);
    expect(
      isPrivateSubscriptionPlan({
        is_private_equipment: true,
        coach_price: null,
        branch_price: null,
      }),
    ).toBe(true);
    expect(isPrivateSubscriptionPlan({ activity_types: [{ code: "private_training" }] })).toBe(
      true,
    );
    expect(
      isPrivateSubscriptionPlan(
        { is_private_equipment: true },
        { code: "general_training", name: "تدريب عام", is_private_equipment: false },
      ),
    ).toBe(true);
    expect(isPrivateSubscriptionPlan(null)).toBe(false);
  });

  it("calculates duration totals and discounts in both directions", () => {
    expect(
      getSubscriptionOriginalAmounts(
        { base_price: 300000, coach_price: 200000, branch_price: 100000 },
        2,
      ),
    ).toEqual({ originalTotal: 600000, coachOriginal: 400000, branchOriginal: 200000 });
    expect(calculateDiscountFromFinalPrice(300000, 150000)).toEqual({
      finalPrice: 150000,
      discountAmount: 150000,
      discountPercentage: 50,
    });
    expect(calculateDiscountFromPercentage(300000, 25)).toEqual({
      finalPrice: 225000,
      discountAmount: 75000,
      discountPercentage: 25,
    });
    expect(calculateDiscountFromPercentage(300000, 140).discountPercentage).toBe(100);
    expect(calculateDiscountFromFinalPrice(300000, -10).finalPrice).toBe(0);
  });

  it("uses the offer price as the original subscription amount", () => {
    expect(
      getSubscriptionDiscountSummary({
        offer: { id: 10, price: 350 },
        plan: { base_price: 500 },
        total_amount: 350,
      }),
    ).toMatchObject({
      originalTotal: 350,
      finalPrice: 350,
      isDiscount: false,
    });
  });

  it("reads split payment amounts and falls back to private-plan prices", () => {
    expect(
      getSubscriptionSplitPaymentAmounts({
        payments: [
          { reason: "دفعة اشتراك المدرب", amount: 175 },
          { reason: "دفعة اشتراك النادي", amount: 175 },
        ],
        plan: { coach_price: "250.00", branch_price: "250.00" },
      }),
    ).toEqual({ coachPaidAmount: 175, branchPaidAmount: 175 });

    expect(
      getSubscriptionSplitPaymentAmounts({
        payments: [{ reason: null, amount: 350 }],
        paid_amount: "350.00",
        plan: { coach_price: "250.00", branch_price: "250.00" },
      }),
    ).toEqual({ coachPaidAmount: 175, branchPaidAmount: 175 });
  });

  it("uses only the new plan prices when existing payment amounts are disabled", () => {
    expect(
      getSubscriptionSplitPaymentAmounts(
        {
          paid_amount: "300.00",
          payments: [
            { reason: "دفعة اشتراك المدرب", amount: 175 },
            { reason: "دفعة اشتراك النادي", amount: 125 },
          ],
        },
        { coach_price: "400.00", branch_price: "250.00" },
        "650.00",
        false,
      ),
    ).toEqual({ coachPaidAmount: 400, branchPaidAmount: 250 });
  });

  it("reads the existing subscription activity type from its plan", () => {
    expect(
      getSubscriptionActivityTypeId({
        plan: {
          activity_types: [{ id: 5, name: "تدريب خاص" }],
          activities: [{ activity_type_id: 6 }],
        },
      }),
    ).toBe("5");
    expect(getSubscriptionActivityTypeId({ plan: { activities: [{ activity_type_id: 6 }] } })).toBe(
      "6",
    );
  });

  it("formats a local date for subscription fields", () => {
    expect(getLocalDateValue(new Date(2026, 7, 8, 12))).toBe("2026-08-08");
    expect(getLocalDateValue(new Date("invalid"))).toBe("");
  });

  it("calculates a full calendar month minus one day", () => {
    expect(getSubscriptionEndDate("2026-08-08")).toBe("2026-09-07");
    expect(getSubscriptionEndDate("2026-12-15")).toBe("2027-01-14");
  });

  it("clamps calendar anniversaries at shorter month endings", () => {
    expect(getSubscriptionEndDate("2026-01-31")).toBe("2026-02-27");
    expect(getSubscriptionEndDate("2028-01-31")).toBe("2028-02-28");
  });

  it("supports multiple subscription months", () => {
    expect(getSubscriptionEndDate("2026-08-08", 3)).toBe("2026-11-07");
    expect(getSubscriptionEndDate("invalid", 1)).toBe("");
  });

  it("detects daily-entry plans from API type fields", () => {
    expect(isDailyEntrySubscriptionPlan({ type: "daily_entry" })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ plan_type: "day-pass" })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ is_daily_entry: true })).toBe(true);
  });

  it("detects localized daily-entry plan names as a fallback", () => {
    expect(isDailyEntrySubscriptionPlan({ name: { ar: "دخولية أجهزة" } })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ name: { en: "Daily Entry" } })).toBe(true);
    expect(isDailyEntrySubscriptionPlan({ name: "اشتراك أجهزة شهري" })).toBe(false);
  });

  it("selects the active subscription belonging to the requested member", () => {
    const response = {
      data: [
        { id: 8, member_id: 2, status: "active" },
        { id: 7, member: { id: 5 }, status: "frozen" },
        { id: 6, member_id: 5, status: "active" },
      ],
    };

    expect(getCurrentMemberSubscription(response, 5)).toMatchObject({ id: 6 });
    expect(getCurrentMemberSubscription(response, 99)).toBeNull();
  });

  it("falls back to the newest subscription when member identifiers are omitted", () => {
    expect(
      getCurrentMemberSubscription(
        {
          data: [
            { id: 1, status: "expired", start_date: "2025-01-01" },
            { id: 2, status: "expired", start_date: "2026-01-01" },
          ],
        },
        5,
      ),
    ).toMatchObject({ id: 2 });
  });
});

import { describe, expect, it } from "vitest";
import {
  createSubscriptionsReportParams,
  normalizeSubscriptionReportRecord,
  normalizeSubscriptionsReportResponse,
  validateSubscriptionsReportFilters,
} from "./subscriptionReportUtils";

describe("subscriptionReportUtils", () => {
  it("builds only the API filters that have an effective value", () => {
    expect(
      createSubscriptionsReportParams(
        {
          status: "active",
          planId: "5",
          paymentStatus: "all",
          coachId: "2",
          startDate: "2026-07-01",
          endDate: "2026-07-31",
          search: "  أحمد  ",
        },
        "1",
      ),
    ).toEqual({
      status: "active",
      plan_id: "5",
      coach_id: "2",
      branch_id: "1",
      start_date: "2026-07-01",
      end_date: "2026-07-31",
      search: "أحمد",
    });
  });

  it("omits all-branch and all-status values", () => {
    expect(
      createSubscriptionsReportParams({ status: "all", paymentStatus: "all", search: "" }, "all"),
    ).toEqual({});
  });

  it("normalizes the documented report response and numeric strings", () => {
    const result = normalizeSubscriptionsReportResponse({
      status: "success",
      data: {
        summary: {
          total_subscriptions: "4",
          total_revenue: "250000",
          currency_type: "SYP",
        },
        records: [{ id: 1 }],
      },
    });

    expect(result.summary.total_subscriptions).toBe(4);
    expect(result.summary.total_revenue).toBe(250000);
    expect(result.summary.total_paid).toBe(0);
    expect(result.summary.currency).toBe("SYP");
    expect(result.records).toEqual([{ id: 1 }]);
  });

  it("normalizes common nested subscription record fields", () => {
    expect(
      normalizeSubscriptionReportRecord({
        id: 9,
        member: {
          member_number: "M-12",
          person: { full_name: "أحمد محمد", phone: "0999000000" },
        },
        plan: { name: { ar: "الخطة الذهبية" } },
        coach: { person: { full_name: "كوتش سامر" } },
        total_amount: "100000",
        paid_amount: "70000",
        remaining_amount: "30000",
        status: "active",
        payment_status: "partially_paid",
      }),
    ).toMatchObject({
      id: 9,
      membershipNumber: "M-12",
      memberName: "أحمد محمد",
      phone: "0999000000",
      planName: "الخطة الذهبية",
      coachName: "كوتش سامر",
      totalAmount: 100000,
      paidAmount: 70000,
      remainingAmount: 30000,
      statusLabel: "فعال",
      paymentStatusLabel: "مدفوع جزئياً",
    });
  });

  it("rejects an inverted date range", () => {
    expect(
      validateSubscriptionsReportFilters({
        startDate: "2026-07-31",
        endDate: "2026-07-01",
      }),
    ).toBeTruthy();
  });
});

import { describe, expect, it } from "vitest";
import {
  createFrozenTerminatedParams,
  normalizeFrozenTerminatedRecord,
  normalizeFrozenTerminatedResponse,
  validateFrozenTerminatedFilters,
  createPrintableFrozenTerminatedReport,
  getMemberPrimaryPhone,
} from "./frozenTerminatedReportUtils";

describe("frozenTerminatedReportUtils", () => {
  it("builds query parameters accurately from filters", () => {
    const filters = {
      status: "frozen",
      dateFilterBy: "event_date",
      startDate: "2026-09-01",
      endDate: "2026-09-30",
      planId: "3",
      search: " مايا ",
    };

    const params = createFrozenTerminatedParams(filters, "5");

    expect(params).toEqual({
      status: "frozen",
      date_filter_by: "event_date",
      start_date: "2026-09-01",
      end_date: "2026-09-30",
      branch_id: "5",
      plan_id: "3",
      search: "مايا",
    });
  });

  it("extracts phone number correctly from record or contacts", () => {
    const withPhone = { member_phone: "0933112233" };
    expect(getMemberPrimaryPhone(withPhone)).toBe("0933112233");

    const withContact = {
      member_phone: "N/A",
      contact_persons: [{ phone_number: "0944556677" }],
    };
    expect(getMemberPrimaryPhone(withContact)).toBe("0944556677");

    const empty = { member_phone: "N/A", contact_persons: [] };
    expect(getMemberPrimaryPhone(empty)).toBe("-");
  });

  it("normalizes a frozen record correctly", () => {
    const raw = {
      subscription_id: 201,
      status: "frozen",
      member_name: "سارة أحمد",
      member_number: "MEM-001",
      plan_name: "أجهزة عام",
      event_date: "2026-09-10",
      reason: "سفر لمدة أسبوعين",
      frozen_days: 14,
      total_amount: 350,
      paid_amount: 350,
      remaining_amount: 0,
    };

    const normalized = normalizeFrozenTerminatedRecord(raw, 0);

    expect(normalized.id).toBe(201);
    expect(normalized.status).toBe("frozen");
    expect(normalized.statusLabel).toBe("مجمّد");
    expect(normalized.isFrozen).toBe(true);
    expect(normalized.reason).toBe("سفر لمدة أسبوعين");
    expect(normalized.frozenDays).toBe(14);
  });

  it("normalizes a terminated record correctly", () => {
    const raw = {
      subscription_id: 305,
      status: "terminated",
      member_name: "خالد عمر",
      reason: "ظروف صحية طارئة",
      total_amount: 500,
      paid_amount: 250,
      remaining_amount: 250,
      lost_revenue: 250,
    };

    const normalized = normalizeFrozenTerminatedRecord(raw, 0);

    expect(normalized.status).toBe("terminated");
    expect(normalized.statusLabel).toBe("ملغى");
    expect(normalized.isFrozen).toBe(false);
    expect(normalized.reason).toBe("ظروف صحية طارئة");
  });

  it("normalizes entire response with summary", () => {
    const rawResponse = {
      status: "success",
      data: {
        summary: {
          total_records: "10",
          total_frozen: "6",
          total_terminated: "4",
          total_frozen_revenue: "1500",
          total_lost_terminated_revenue: "900",
          currency: "SYP",
        },
        records: [
          { subscription_id: 1, status: "frozen", member_name: "عمر" },
          { subscription_id: 2, status: "terminated", member_name: "ليلى" },
        ],
      },
    };

    const result = normalizeFrozenTerminatedResponse(rawResponse);

    expect(result.summary.total_records).toBe(10);
    expect(result.summary.total_frozen).toBe(6);
    expect(result.summary.total_terminated).toBe(4);
    expect(result.summary.total_frozen_revenue).toBe(1500);
    expect(result.summary.total_lost_terminated_revenue).toBe(900);
    expect(result.records).toHaveLength(2);
  });

  it("validates date range filters", () => {
    expect(
      validateFrozenTerminatedFilters({
        startDate: "2026-10-01",
        endDate: "2026-09-01",
      })
    ).toBe("يجب أن يكون تاريخ البداية قبل تاريخ النهاية أو مساوياً له.");

    expect(
      validateFrozenTerminatedFilters({
        startDate: "2026-09-01",
        endDate: "2026-10-01",
      })
    ).toBe("");
  });

  it("creates printable report format", () => {
    const rows = [
      normalizeFrozenTerminatedRecord({
        subscription_id: 10,
        member_name: "ياسمين",
        status: "frozen",
        event_date: "2026-09-05",
        reason: "إصابة",
        total_amount: 300,
      }),
    ];
    const summary = {
      total_records: 1,
      total_frozen: 1,
      total_terminated: 0,
      total_frozen_revenue: 300,
      total_lost_terminated_revenue: 0,
      currency_type: "SYP",
    };

    const printable = createPrintableFrozenTerminatedReport(rows, summary);

    expect(printable.id).toBe("subscriptions-frozen-terminated");
    expect(printable.metrics).toHaveLength(5);
    expect(printable.rows).toHaveLength(1);
  });
});

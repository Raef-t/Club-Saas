import { describe, expect, it } from "vitest";
import {
  createRenewalReportParams,
  normalizeRenewalRecord,
  normalizeRenewalReportResponse,
  validateRenewalReportFilters,
  createPrintableRenewalReport,
  getMemberPrimaryPhone,
} from "./renewalStatusReportUtils";

describe("renewalStatusReportUtils", () => {
  it("builds query parameters accurately from filters", () => {
    const filters = {
      type: "expired_non_renewed",
      dateFilterBy: "end_date",
      startDate: "2026-09-01",
      endDate: "2026-09-30",
      planId: "12",
      coachId: "4",
      search: " روان ",
    };

    const params = createRenewalReportParams(filters, "5");

    expect(params).toEqual({
      type: "expired_non_renewed",
      date_filter_by: "end_date",
      start_date: "2026-09-01",
      end_date: "2026-09-30",
      branch_id: "5",
      plan_id: "12",
      coach_id: "4",
      search: "روان",
    });
  });

  it("handles 'all' branch and omits empty search or plan", () => {
    const filters = {
      type: "all",
      dateFilterBy: "created_date",
      startDate: "",
      endDate: "",
      planId: "",
      coachId: "",
      search: "",
    };

    const params = createRenewalReportParams(filters, "all");

    expect(params).toEqual({
      type: "all",
      date_filter_by: "created_date",
    });
  });

  it("extracts phone number correctly from record or contact persons", () => {
    const recordWithPhone = {
      member_phone: "0987654321",
    };
    expect(getMemberPrimaryPhone(recordWithPhone)).toBe("0987654321");

    const recordWithContact = {
      member_phone: "N/A",
      contact_persons: [{ name: "Father", phone_number: "0912345678" }],
    };
    expect(getMemberPrimaryPhone(recordWithContact)).toBe("0912345678");

    const recordEmpty = {
      member_phone: "N/A",
      contact_persons: [],
    };
    expect(getMemberPrimaryPhone(recordEmpty)).toBe("-");
  });

  it("normalizes a non-renewed record correctly", () => {
    const raw = {
      subscription_id: 111,
      status_type: "expired_non_renewed",
      status_label: "منتهي ولم يجدد",
      member_id: 54,
      member_number: "MEM-2026-0008",
      member_name: "روان سودة",
      member_phone: "N/A",
      contact_persons: [
        {
          id: 252,
          name: "Personal",
          phone_number: "986057151",
          relation: "self",
        },
      ],
      absence_period: {
        last_attendance_date: null,
        formatted: "لم يحضر أبدًا",
      },
      branch_name: "تكنو جيم بنات",
      plan_id: 33,
      plan_name: "أجهزة خاص - آية مزور - 3 ايام",
      coaches_names: "آية مزور",
      start_date: "2026-09-05",
      end_date: "2027-03-10",
      total_amount: 225,
      paid_amount: 350,
      remaining_amount: 0,
      days_since_expiration: 0,
    };

    const normalized = normalizeRenewalRecord(raw, 0);

    expect(normalized.id).toBe(111);
    expect(normalized.statusType).toBe("expired_non_renewed");
    expect(normalized.memberPhone).toBe("986057151");
    expect(normalized.absenceFormatted).toBe("لم يحضر أبدًا");
    expect(normalized.totalAmount).toBe(225);
    expect(normalized.paidAmount).toBe(350);
  });

  it("normalizes a renewed record and renewal_info correctly", () => {
    const raw = {
      subscription_id: 118,
      status_type: "renewed",
      status_label: "تم التجديد",
      member_id: 61,
      member_name: "رندا مسلاتي",
      renewal_info: {
        renewed_subscription_id: 119,
        new_plan_name: "أجهزة خاص - حلا عابد - يومي",
        renewal_date: "2026-09-05",
        new_end_date: "2026-10-04",
        new_total_amount: 300,
        new_currency: "SYP",
      },
    };

    const normalized = normalizeRenewalRecord(raw, 0);

    expect(normalized.statusType).toBe("renewed");
    expect(normalized.statusLabel).toBe("تم التجديد");
    expect(normalized.renewalInfo).toBeTruthy();
    expect(normalized.renewalInfo.renewed_subscription_id).toBe(119);
  });

  it("normalizes entire response with summary", () => {
    const rawResponse = {
      status: "success",
      data: {
        summary: {
          total_records: "80",
          total_expired_non_renewed: "79",
          total_renewed: "1",
          renewal_rate_percentage: "1.25",
          total_lost_potential_revenue: "23930",
          total_renewed_revenue: "300",
          currency: "SYP",
          currency_type: "SYP",
        },
        records: [{ subscription_id: 1, member_name: "لاعب تجريبي" }],
      },
    };

    const result = normalizeRenewalReportResponse(rawResponse);

    expect(result.summary.total_records).toBe(80);
    expect(result.summary.total_expired_non_renewed).toBe(79);
    expect(result.summary.total_renewed).toBe(1);
    expect(result.summary.renewal_rate_percentage).toBe(1.25);
    expect(result.summary.total_lost_potential_revenue).toBe(23930);
    expect(result.records).toHaveLength(1);
  });

  it("validates date filters correctly", () => {
    expect(
      validateRenewalReportFilters({
        startDate: "2026-10-01",
        endDate: "2026-09-01",
      })
    ).toBe("يجب أن يكون تاريخ البداية قبل تاريخ النهاية أو مساوياً له.");

    expect(
      validateRenewalReportFilters({
        startDate: "2026-09-01",
        endDate: "2026-10-01",
      })
    ).toBe("");
  });

  it("creates printable report structure", () => {
    const rows = [
      normalizeRenewalRecord({
        subscription_id: 111,
        member_name: "روان سودة",
        end_date: "2026-10-01",
        days_since_expiration: 2,
        total_amount: 500,
        currency_type: "SYP",
      }),
    ];
    const summary = {
      total_records: 1,
      total_expired_non_renewed: 1,
      total_renewed: 0,
      renewal_rate_percentage: 0,
      total_lost_potential_revenue: 500,
      total_renewed_revenue: 0,
      currency_type: "SYP",
    };

    const printable = createPrintableRenewalReport(rows, summary);

    expect(printable.id).toBe("subscriptions-renewal-status");
    expect(printable.metrics).toHaveLength(6);
    expect(printable.rows).toHaveLength(1);
  });
});

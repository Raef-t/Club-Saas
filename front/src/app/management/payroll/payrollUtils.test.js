import { describe, expect, it } from "vitest";
import {
  calculatePayslipTotals,
  createConfirmPayload,
  getPayrollDraft,
  getPayrollEndDay,
  getPayslips,
  updateDraftPayslip,
} from "./payrollUtils";

describe("payroll utils", () => {
  it("unwraps generated drafts and normalizes numeric values", () => {
    const draft = getPayrollDraft({
      data: {
        period_start: "2026-07-28",
        period_end: "2026-08-28",
        payslips: [{ staff_id: 1, base_pay: "1000", commission_pay: 100 }],
      },
    });

    expect(draft.payslips[0]).toMatchObject({ base_pay: 1000, commission_pay: 100, net_pay: 1100 });
  });

  it("calculates bonuses and deductions from adjustments", () => {
    expect(
      calculatePayslipTotals({
        base_pay: 1000,
        commission_pay: 100,
        adjustments: [
          { type: "bonus", amount: 75 },
          { type: "deduction", amount: 25 },
        ],
      }),
    ).toEqual({ bonuses: 75, deductions: 25, netPay: 1150 });
  });

  it("recalculates a locally edited draft", () => {
    const updated = updateDraftPayslip(
      { staff_id: 3, base_pay: 500, commission_pay: 0, adjustments: [] },
      { adjustments: [{ type: "deduction", amount: 50, reason: "غياب" }] },
    );
    expect(updated.net_pay).toBe(450);
    expect(updated.deductions).toBe(50);
  });

  it("creates the confirm contract", () => {
    const payload = createConfirmPayload("5", {
      period_start: "2026-07-28",
      period_end: "2026-08-28",
      payslips: [
        {
          staff_id: 2,
          base_pay: 1000,
          commission_pay: 10,
          net_pay: 1010,
          adjustments: [],
        },
      ],
    });
    expect(payload).toEqual({
      branch_id: 5,
      period_start: "2026-07-28",
      period_end: "2026-08-28",
      payslips: [
        { staff_id: 2, base_pay: 1000, commission_pay: 10, net_pay: 1010, adjustments: [] },
      ],
    });
  });

  it("supports common list and settings envelopes", () => {
    expect(getPayslips({ data: { data: [{ id: 1 }] } })).toEqual([{ id: 1 }]);
    expect(getPayrollEndDay({ data: { payroll_end_day: 28 } })).toBe(28);
  });
});

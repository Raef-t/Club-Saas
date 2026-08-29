/**
 * Unwraps the response envelope returned by TechnoGYM endpoints.
 */
export function getResponseData(response) {
  if (response?.data?.data !== undefined) return response.data.data;
  if (response?.data !== undefined) return response.data;
  return response;
}

export function getPayslips(response) {
  const data = getResponseData(response);
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.payslips)) return data.payslips;
  return [];
}

export function getPayrollDraft(response) {
  const data = getResponseData(response);
  if (!data || typeof data !== "object" || !Array.isArray(data.payslips)) return null;

  return {
    period_start: data.period_start || "",
    period_end: data.period_end || "",
    staff_count: Number(data.staff_count ?? data.payslips.length) || 0,
    payslips: data.payslips.map(normalizePayslip),
  };
}

export function normalizePayslip(payslip) {
  const basePay = Number(payslip?.base_pay) || 0;
  const commissionPay = Number(payslip?.commission_pay) || 0;
  const adjustments = Array.isArray(payslip?.adjustments)
    ? payslip.adjustments.map((adjustment) => ({
        id: adjustment.id,
        type: adjustment.type === "deduction" ? "deduction" : "bonus",
        amount: Number(adjustment.amount) || 0,
        reason: adjustment.reason || "",
      }))
    : [];
  const calculated = calculatePayslipTotals({
    base_pay: basePay,
    commission_pay: commissionPay,
    adjustments,
  });

  return {
    ...payslip,
    base_pay: basePay,
    commission_pay: commissionPay,
    subscribers_count:
      Number(
        payslip?.subscribers_count ??
          payslip?.subscribers_coun ??
          payslip?.subscriptions_count ??
          payslip?.subscribers?.length,
      ) || 0,
    adjustments,
    bonuses: payslip?.bonuses == null ? calculated.bonuses : Number(payslip.bonuses) || 0,
    deductions:
      payslip?.deductions == null ? calculated.deductions : Number(payslip.deductions) || 0,
    net_pay: payslip?.net_pay == null ? calculated.netPay : Number(payslip.net_pay) || 0,
  };
}

export function calculatePayslipTotals(payslip) {
  const adjustments = Array.isArray(payslip?.adjustments) ? payslip.adjustments : [];
  const bonuses = adjustments
    .filter((item) => item.type === "bonus")
    .reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
  const deductions = adjustments
    .filter((item) => item.type === "deduction")
    .reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
  const netPay =
    (Number(payslip?.base_pay) || 0) +
    (Number(payslip?.commission_pay) || 0) +
    bonuses -
    deductions;

  return { bonuses, deductions, netPay };
}

export function updateDraftPayslip(payslip, changes) {
  const updated = normalizePayslip({ ...payslip, ...changes });
  const totals = calculatePayslipTotals(updated);
  return {
    ...updated,
    bonuses: totals.bonuses,
    deductions: totals.deductions,
    net_pay: totals.netPay,
  };
}

export function createUpdatePayload(payslip) {
  return {
    base_pay: Number(payslip.base_pay) || 0,
    commission_pay: Number(payslip.commission_pay) || 0,
    adjustments: (payslip.adjustments || []).map((adjustment) => ({
      type: adjustment.type,
      amount: Number(adjustment.amount) || 0,
      reason: String(adjustment.reason || "").trim(),
    })),
  };
}

export function createConfirmPayload(branchId, draft) {
  return {
    branch_id: Number(branchId),
    period_start: draft.period_start,
    period_end: draft.period_end,
    payslips: draft.payslips.map((payslip) => ({
      staff_id: Number(payslip.staff_id),
      ...createUpdatePayload(payslip),
      net_pay: Number(payslip.net_pay) || 0,
    })),
  };
}

export function getPayslipStaffName(payslip) {
  return (
    payslip?.staff_name ||
    payslip?.staff?.person?.full_name ||
    payslip?.staff?.name ||
    `موظف #${payslip?.staff_id || payslip?.id || "-"}`
  );
}

export function getPayslipBranchId(payslip) {
  return payslip?.branch_id ?? payslip?.branch?.id ?? payslip?.staff?.branch_id ?? null;
}

export function filterPayslipsByBranch(payslips, branchId) {
  if (!branchId || branchId === "all") return payslips;
  const hasBranchInformation = payslips.some((payslip) => getPayslipBranchId(payslip) != null);
  if (!hasBranchInformation) return payslips;
  return payslips.filter((payslip) => String(getPayslipBranchId(payslip)) === String(branchId));
}

export function getPayrollEndDay(settingsResponse) {
  const settings = getResponseData(settingsResponse);
  const day = Number(settings?.payroll_end_day);
  return Number.isInteger(day) && day >= 1 && day <= 31 ? day : null;
}

export function getPayrollPeriodLabel(start, end) {
  if (!start || !end) return "لم يتم توليد فترة بعد";
  const formatter = new Intl.DateTimeFormat("ar-SY", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
  return `${formatter.format(new Date(start))} — ${formatter.format(new Date(end))}`;
}

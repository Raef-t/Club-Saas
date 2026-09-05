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
  const adjustmentTotals = calculateAdjustmentTotals(adjustments);
  const reportedBonuses = numberFromAliases(payslip, ["bonuses", "bonus_total"]);
  const reportedDeductions = numberFromAliases(payslip, [
    "deductions",
    "deduction_total",
    "total_deductions",
  ]);
  const explicitSystemBonuses = numberFromAliases(payslip, ["system_bonuses", "automatic_bonuses"]);
  const explicitSystemDeductions = numberFromAliases(payslip, [
    "system_deductions",
    "automatic_deductions",
    "advance_deductions",
    "advances_deducted",
    "salary_advance_deductions",
  ]);
  const systemBonuses =
    explicitSystemBonuses ??
    Math.max(0, (reportedBonuses ?? adjustmentTotals.bonuses) - adjustmentTotals.bonuses);
  const systemDeductions =
    explicitSystemDeductions ??
    Math.max(0, (reportedDeductions ?? adjustmentTotals.deductions) - adjustmentTotals.deductions);
  const calculated = calculatePayslipTotals({
    base_pay: basePay,
    commission_pay: commissionPay,
    adjustments,
    system_bonuses: systemBonuses,
    system_deductions: systemDeductions,
  });

  const branchId =
    payslip?.branch_id ?? payslip?.branch_ids?.[0] ?? payslip?.staff?.branch_id ?? null;
  const branchIds =
    Array.isArray(payslip?.branch_ids) && payslip.branch_ids.length > 0
      ? payslip.branch_ids
      : branchId != null
        ? [branchId]
        : [];

  return {
    ...payslip,
    branch_id: branchId,
    branch_ids: branchIds,
    status: payslip?.status || "pending",
    paid_at: payslip?.paid_at || null,
    salary_payments: payslip?.salary_payments || [],
    base_pay: basePay,
    commission_pay: commissionPay,
    system_bonuses: systemBonuses,
    system_deductions: systemDeductions,
    subscribers_count:
      Number(
        payslip?.subscribers_count ??
          payslip?.subscribers_coun ??
          payslip?.subscriptions_count ??
          payslip?.subscribers?.length,
      ) || 0,
    adjustments,
    bonuses: calculated.bonuses,
    deductions: calculated.deductions,
    net_pay: calculated.netPay,
  };
}

export function calculatePayslipTotals(payslip) {
  const adjustments = Array.isArray(payslip?.adjustments) ? payslip.adjustments : [];
  const adjustmentTotals = calculateAdjustmentTotals(adjustments);
  const bonuses = (Number(payslip?.system_bonuses) || 0) + adjustmentTotals.bonuses;
  const deductions = (Number(payslip?.system_deductions) || 0) + adjustmentTotals.deductions;

  return {
    bonuses,
    deductions,
    netPay:
      (Number(payslip?.base_pay) || 0) +
      (Number(payslip?.commission_pay) || 0) +
      bonuses -
      deductions,
  };
}

function calculateAdjustmentTotals(adjustments) {
  return adjustments.reduce(
    (totals, item) => {
      const amount = Number(item?.amount) || 0;
      if (item?.type === "deduction") totals.deductions += amount;
      if (item?.type === "bonus") totals.bonuses += amount;
      return totals;
    },
    { bonuses: 0, deductions: 0 },
  );
}

function numberFromAliases(record, aliases) {
  for (const alias of aliases) {
    if (record?.[alias] !== null && record?.[alias] !== undefined && record?.[alias] !== "") {
      const value = Number(record[alias]);
      if (Number.isFinite(value)) return value;
    }
  }
  return null;
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

export function createPayrollAction(searchParams = {}) {
  const action = Array.isArray(searchParams.payroll_action)
    ? searchParams.payroll_action[0]
    : searchParams.payroll_action;
  const rawBranchId = Array.isArray(searchParams.branch_id)
    ? searchParams.branch_id[0]
    : searchParams.branch_id;
  const branchId = Number(rawBranchId);

  if (action !== "generate" || !Number.isInteger(branchId) || branchId <= 0) return null;

  return {
    type: "generate",
    branchId: String(branchId),
    notificationId:
      searchParams.notification_id == null ? null : String(searchParams.notification_id),
    periodStart: typeof searchParams.period_start === "string" ? searchParams.period_start : "",
    periodEnd: typeof searchParams.period_end === "string" ? searchParams.period_end : "",
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
  return payslips.filter((payslip) => {
    if (Array.isArray(payslip?.branch_ids) && payslip.branch_ids.length > 0) {
      return payslip.branch_ids.some((id) => String(id) === String(branchId));
    }
    const slipBranchId = getPayslipBranchId(payslip);
    return slipBranchId != null ? String(slipBranchId) === String(branchId) : true;
  });
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

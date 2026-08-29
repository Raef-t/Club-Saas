"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import Dropdown from "@/components/ui/Dropdown";
import { PlusIcon, TrashIcon } from "@/components/icons/Icons";
import { formatMoney } from "@/lib/utils";
import { calculatePayslipTotals, getPayslipStaffName } from "./payrollUtils";

const ADJUSTMENT_OPTIONS = [
  { value: "bonus", label: "مكافأة" },
  { value: "deduction", label: "خصم" },
];

function createAdjustment() {
  return {
    key: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
    type: "bonus",
    amount: "",
    reason: "",
  };
}

export default function PayslipEditorModal({ payslip, onClose, onSave, isSaving }) {
  const [form, setForm] = useState({ base_pay: "", commission_pay: "", adjustments: [] });

  useEffect(() => {
    if (!payslip) return;
    setForm({
      base_pay: String(payslip.base_pay ?? 0),
      commission_pay: String(payslip.commission_pay ?? 0),
      adjustments: (payslip.adjustments || []).map((adjustment, index) => ({
        ...adjustment,
        key: adjustment.id || `${index}-${adjustment.type}-${adjustment.amount}`,
        amount: String(adjustment.amount ?? ""),
      })),
    });
  }, [payslip]);

  const totals = useMemo(
    () =>
      calculatePayslipTotals({
        base_pay: form.base_pay,
        commission_pay: form.commission_pay,
        adjustments: form.adjustments,
      }),
    [form],
  );

  function setAdjustment(key, field, value) {
    setForm((current) => ({
      ...current,
      adjustments: current.adjustments.map((item) =>
        item.key === key ? { ...item, [field]: value } : item,
      ),
    }));
  }

  function removeAdjustment(key) {
    setForm((current) => ({
      ...current,
      adjustments: current.adjustments.filter((item) => item.key !== key),
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();
    onSave({
      base_pay: Number(form.base_pay) || 0,
      commission_pay: Number(form.commission_pay) || 0,
      adjustments: form.adjustments.map(({ key, id, ...adjustment }) => ({
        ...(id == null ? {} : { id }),
        ...adjustment,
        amount: Number(adjustment.amount) || 0,
        reason: adjustment.reason.trim(),
      })),
    });
  }

  return (
    <Modal
      open={Boolean(payslip)}
      onClose={isSaving ? undefined : onClose}
      title="تعديل سجل الراتب"
      subtitle={payslip ? getPayslipStaffName(payslip) : ""}
      className="max-w-4xl"
    >
      <form className="space-y-6" onSubmit={handleSubmit}>
        <div className="grid gap-4 sm:grid-cols-2">
          <MoneyField
            label="الراتب الأساسي"
            value={form.base_pay}
            onChange={(value) => setForm((current) => ({ ...current, base_pay: value }))}
          />
          <MoneyField
            label="النسبة"
            value={form.commission_pay}
            onChange={(value) => setForm((current) => ({ ...current, commission_pay: value }))}
          />
        </div>

        <section className="rounded-xl border border-app-line bg-app-panel-soft p-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 className="text-sm font-medium text-app-text">الخصومات والمكافآت</h3>
              <p className="mt-1 text-xs text-app-muted-light">
                أضف أي تعديل مع المبلغ والسبب، وسيُعاد حساب الصافي تلقائياً.
              </p>
            </div>
            <Button
              type="button"
              tone="outline"
              className="h-9 px-3 text-xs"
              icon={<PlusIcon className="size-4" />}
              onClick={() =>
                setForm((current) => ({
                  ...current,
                  adjustments: [...current.adjustments, createAdjustment()],
                }))
              }
            >
              إضافة تعديل
            </Button>
          </div>

          <div className="mt-4 space-y-3">
            {form.adjustments.length === 0 ? (
              <p className="rounded-lg border border-dashed border-app-line p-5 text-center text-xs text-app-muted-light">
                لا توجد خصومات أو مكافآت.
              </p>
            ) : (
              form.adjustments.map((adjustment) => (
                <div
                  key={adjustment.key}
                  className="grid gap-3 rounded-lg border border-app-line bg-app-card p-3 sm:grid-cols-[150px_150px_minmax(0,1fr)_40px]"
                >
                  <Dropdown
                    value={adjustment.type}
                    options={ADJUSTMENT_OPTIONS}
                    onChange={(value) => setAdjustment(adjustment.key, "type", value)}
                    className="text-app-text"
                  />
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    className="app-input h-10 px-3 text-sm text-app-text"
                    value={adjustment.amount}
                    onChange={(event) =>
                      setAdjustment(adjustment.key, "amount", event.target.value)
                    }
                    placeholder="المبلغ"
                    aria-label="مبلغ التعديل"
                  />
                  <input
                    type="text"
                    required
                    className="app-input h-10 px-3 text-sm text-app-text"
                    value={adjustment.reason}
                    onChange={(event) =>
                      setAdjustment(adjustment.key, "reason", event.target.value)
                    }
                    placeholder="سبب الخصم أو المكافأة"
                    aria-label="سبب التعديل"
                  />
                  <button
                    type="button"
                    className="grid size-10 place-items-center rounded-lg border border-app-red/20 bg-app-red/10 text-app-red transition hover:bg-app-red/20"
                    onClick={() => removeAdjustment(adjustment.key)}
                    aria-label="حذف التعديل"
                  >
                    <TrashIcon className="size-4" />
                  </button>
                </div>
              ))
            )}
          </div>
        </section>

        <div className="grid gap-3 sm:grid-cols-3">
          <TotalCard label="المكافآت" value={totals.bonuses} tone="green" />
          <TotalCard label="الخصومات" value={totals.deductions} tone="red" />
          <TotalCard label="صافي الراتب" value={totals.netPay} tone="yellow" />
        </div>

        <div className="flex justify-end gap-3 border-t border-app-line pt-5">
          <Button type="button" tone="outline" onClick={onClose} disabled={isSaving}>
            إلغاء
          </Button>
          <Button type="submit" loading={isSaving} loadingLabel="جاري الحفظ">
            حفظ التعديلات
          </Button>
        </div>
      </form>
    </Modal>
  );
}

function MoneyField({ label, value, onChange }) {
  return (
    <label className="block text-right">
      <span className="mb-2 block text-sm font-medium text-app-text">{label}</span>
      <input
        type="number"
        min="0"
        step="0.01"
        required
        className="app-input h-11 w-full px-3 text-sm text-app-text"
        value={value}
        onChange={(event) => onChange(event.target.value)}
      />
    </label>
  );
}

function TotalCard({ label, value, tone }) {
  const toneClass = {
    green: "text-app-green",
    red: "text-app-red",
    yellow: "text-app-yellow",
  }[tone];

  return (
    <div className="rounded-xl border border-app-line bg-app-card-soft p-4 text-center">
      <p className="text-xs text-app-muted-light">{label}</p>
      <p className={`mt-2 text-lg font-semibold ${toneClass}`}>{formatMoney(value)}</p>
    </div>
  );
}

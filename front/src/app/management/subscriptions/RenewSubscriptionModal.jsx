"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import Modal from "@/components/ui/Modal";
import { CURRENCY_SYMBOL, formatLocalizedName, formatMoney } from "@/lib/utils";
import { subscriptionRenewalSchema } from "@/lib/validations/subscriptionsSchema";

function getPlanPrice(plan, subscription) {
  const price = plan?.base_price ?? plan?.price ?? subscription?.total_amount ?? 0;
  const numericPrice = Number(price);
  return Number.isFinite(numericPrice) ? numericPrice : 0;
}

function getPlanName(plan) {
  return formatLocalizedName(plan?.name) || plan?.name || `خطة #${plan?.id}`;
}

/** Collects the payment details required by POST /player-subscriptions/{id}/renew. */
export default function RenewSubscriptionModal({
  open,
  subscription,
  plans = [],
  isPlansLoading = false,
  plansErrorMessage = "",
  onClose,
  onSubmit,
  isLoading = false,
  errorMessage = "",
}) {
  const currentPlan = subscription?.plan || null;
  const availablePlans = useMemo(() => {
    const uniquePlans = new Map();

    if (currentPlan?.id != null) uniquePlans.set(String(currentPlan.id), currentPlan);
    plans.forEach((plan) => {
      if (plan?.id != null) uniquePlans.set(String(plan.id), plan);
    });

    return [...uniquePlans.values()];
  }, [currentPlan, plans]);
  const [form, setForm] = useState({
    plan_id: "",
    paid_amount: "",
    receipt_number: "",
  });
  const [errors, setErrors] = useState({});
  const selectedPlan =
    availablePlans.find((plan) => String(plan.id) === form.plan_id) || currentPlan;
  const selectedPlanPrice = getPlanPrice(selectedPlan, subscription);

  useEffect(() => {
    if (!open || !subscription) return;

    const plan = subscription.plan || null;
    const price = getPlanPrice(plan, subscription);
    setForm({
      plan_id: plan?.id == null ? "" : String(plan.id),
      paid_amount: String(price),
      receipt_number: "",
    });
    setErrors({});
  }, [open, subscription]);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handlePlanChange(planId) {
    const plan = availablePlans.find((item) => String(item.id) === planId);
    const price = getPlanPrice(plan, subscription);

    setForm((current) => ({
      ...current,
      plan_id: planId,
      paid_amount: String(price),
    }));
    setErrors((current) => ({ ...current, plan_id: null, paid_amount: null }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    const result = subscriptionRenewalSchema.safeParse(form);

    if (!result.success) {
      const nextErrors = {};
      result.error.issues.forEach((issue) => {
        nextErrors[issue.path[0]] = issue.message;
      });
      setErrors(nextErrors);
      return;
    }

    setErrors({});
    await onSubmit?.({
      ...result.data,
      payment_method: "cash",
    });
  }

  const memberName = subscription?.member?.person?.full_name;

  return (
    <Modal
      open={open}
      onClose={isLoading ? undefined : onClose}
      title="تجديد الاشتراك"
      subtitle={memberName || (subscription?.id ? `رقم الاشتراك ${subscription.id}` : "")}
      className="max-w-xl"
    >
      <form className="space-y-5" noValidate onSubmit={handleSubmit}>
        <label className="block text-sm text-app-muted-light">
          اسم الاشتراك السابق مع إمكانية التعديل *
          <Dropdown
            ariaLabel="اسم الاشتراك السابق مع إمكانية التعديل"
            className="mt-2 text-app-text"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.plan_id}
            onChange={handlePlanChange}
            disabled={isLoading || (isPlansLoading && availablePlans.length === 0)}
            options={availablePlans.map((plan) => ({
              value: String(plan.id),
              label: getPlanName(plan),
            }))}
            placeholder="اختر خطة الاشتراك"
            error={errors.plan_id}
            searchable
            searchPlaceholder="ابحث عن خطة اشتراك..."
          />
          {plansErrorMessage && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {plansErrorMessage}
            </span>
          )}
        </label>

        <div className="rounded-xl border border-app-yellow/25 bg-app-yellow-soft/40 p-4">
          <p className="text-xs text-app-muted-light">المبلغ الموافق للخطة / الفعالية</p>
          <p className="mt-1 text-xl font-semibold text-app-yellow">
            {formatMoney(selectedPlanPrice)}
          </p>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <label className="block text-sm text-app-muted-light">
            رقم الإيصال *
            <input
              type="text"
              value={form.receipt_number}
              onChange={(event) => updateField("receipt_number", event.target.value)}
              maxLength={100}
              disabled={isLoading}
              aria-invalid={Boolean(errors.receipt_number)}
              className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-app-text outline-none ${
                errors.receipt_number ? "border-app-red" : "focus:border-app-yellow/70"
              }`}
              placeholder="أدخل رقم الإيصال"
              required
            />
            {errors.receipt_number && (
              <span className="mt-1.5 block text-xs text-app-red" role="alert">
                {errors.receipt_number}
              </span>
            )}
          </label>

          <label className="block text-sm text-app-muted-light">
            الكمية المدفوعة ({CURRENCY_SYMBOL}) *
            <input
              type="number"
              min="0"
              step="any"
              value={form.paid_amount}
              onChange={(event) => updateField("paid_amount", event.target.value)}
              disabled={isLoading}
              aria-invalid={Boolean(errors.paid_amount)}
              className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-app-text outline-none ${
                errors.paid_amount ? "border-app-red" : "focus:border-app-yellow/70"
              }`}
              placeholder="0"
              required
            />
            {errors.paid_amount && (
              <span className="mt-1.5 block text-xs text-app-red" role="alert">
                {errors.paid_amount}
              </span>
            )}
          </label>
        </div>

        {errorMessage && (
          <p
            className="rounded-xl border border-app-red/30 bg-app-red/10 px-4 py-3 text-sm text-app-red"
            role="alert"
          >
            {errorMessage}
          </p>
        )}

        <div className="flex justify-end gap-3 border-t border-app-line pt-4">
          <Button type="button" tone="outline" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button type="submit" loading={isLoading} loadingLabel="جاري تجديد الاشتراك">
            تأكيد التجديد
          </Button>
        </div>
      </form>
    </Modal>
  );
}
